<?php

namespace App\Services\Traits;

use App\Models\AiProvider;
use App\Models\AiUsageLog;
use App\Models\Asset;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Trait AiProviderCallerTrait
 *
 * Digunakan oleh AiService untuk semua interaksi dengan provider AI eksternal (Groq/LLM).
 *
 * Method yang tersedia:
 *   - analyzeWithAi()   : Analisis teks laporan via AI provider yang dipilih
 *   - callGroq()        : Kirim request ke Groq API dan catat penggunaan token
 *   - stripJsonFence()  : Hapus markdown code fence dari respons Groq
 *   - getBestProvider() : Pilih provider AI terbaik yang tersedia (healthy + ada kuota)
 */
trait AiProviderCallerTrait
{
    /**
     * Analisis teks laporan menggunakan provider AI (Groq/LLM).
     *
     * Membangun prompt dengan daftar asset dari database,
     * lalu meneruskannya ke Groq. Mengembalikan null jika tidak ada provider
     * yang tersedia atau respons tidak dapat diparse.
     *
     * @param string $text Teks laporan yang akan dianalisis
     * @return array|null Hasil analisis atau null jika AI tidak tersedia/gagal
     */
    protected function analyzeWithAi(string $text): ?array
    {
        $provider = $this->getBestProvider();
        if (!$provider) {
            Log::info('AI Service: No provider available, using keyword fallback');
            return null;
        }

        try {
            // Ambil daftar asset untuk dikirim sebagai konteks ke AI
            $assets = Asset::limit(300)->get()
                ->map(function ($a) {
                    return "ID:{$a->id} | TagNo:{$a->tag_no} | Desc:{$a->description}";
                })
                ->implode("\n");

            $prompt = <<<PROMPT
Kamu adalah asisten analis laporan maintenance pabrik oleochemical.

Teks laporan: "{$text}"

DAFTAR ASSET (peralatan):
{$assets}

TUGAS:
1. Cari ASSET: cocokkan teks dengan tag_no (prioritas utama) atau description. Cari partial match jika user menyebut kode parsial.
2. Jika asset ditemukan, isi detected_asset_id dengan ID numeric dari asset tersebut.
3. Tentukan jenis laporan: equipment_repair (jika ada asset disebut), area_work (jika hanya pekerjaan umum), atau general.
4. confidence: 0-100. Beri tinggi (80-100) jika asset cocok exact/jelas. Rendah (<40) jika hanya partial match.
5. Jika informasi cukup jelas (confidence >= 60), set needs_clarification=false.
6. Jika sama sekali tidak jelas, set needs_clarification=true.

Balas HANYA JSON (tanpa markdown, tanpa tag):
{
  "report_type": "equipment_repair|area_work|general",
  "detected_asset": "tag_no atau null",
  "detected_asset_id": "ID numeric dari asset atau null",
  "confidence": 0-100,
  "needs_clarification": true/false,
  "clarification_questions": ["pertanyaan singkat"],
  "message": "pesan ramah untuk teknisi dalam Bahasa Indonesia"
}
PROMPT;

            $response = $this->callGroq($provider, $prompt);
            if ($response) {
                $cleaned = $this->stripJsonFence($response);
                $parsed  = json_decode($cleaned, true);

                if ($parsed && isset($parsed['report_type'])) {
                    $parsed['suggested_asset'] = $parsed['detected_asset'] ?? null;
                    return $parsed;
                }

                Log::warning('AI Service: Failed to parse JSON response', [
                    'raw'     => substr($response, 0, 200),
                    'cleaned' => substr($cleaned, 0, 200),
                ]);
            }
        } catch (\Exception $e) {
            Log::warning("AI Service analyze error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Kirim prompt ke Groq API menggunakan provider yang dipilih.
     *
     * Mencatat penggunaan token (harian & bulanan) dan menyimpan log ke AiUsageLog.
     * Menandai provider sebagai 'exhausted' jika mendapat respons HTTP 429.
     *
     * @param AiProvider $provider Provider yang akan digunakan
     * @param string     $prompt   Prompt yang dikirim ke model
     * @return string|null Konten respons dari model, atau null jika gagal
     */
    protected function callGroq(AiProvider $provider, string $prompt): ?string
    {
        $startTime    = microtime(true);
        $responseTime = 0;
        $tokensUsed   = 0;
        $status       = 'error';
        $errorMessage = null;

        try {
            $endpoint = $provider->endpoint_url ?? 'https://api.groq.com/openai/v1/chat/completions';
            $model    = $provider->model         ?? 'llama-3.3-70b-versatile';

            // Gunakan accessor api_key (auto-decrypt jika terenkripsi)
            $apiKey = $provider->api_key;

            if (empty($apiKey)) {
                Log::warning("AI Service: API key kosong untuk provider [{$provider->name}]");
                return null;
            }

            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ])
                ->post($endpoint, [
                    'model'    => $model,
                    'messages' => [
                        [
                            'role'    => 'system',
                            'content' => 'Kamu adalah asisten analis laporan maintenance. Balas HANYA dalam format JSON.',
                        ],
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.1,
                    'max_tokens'  => 600,
                ]);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $content    = $response->json()['choices'][0]['message']['content'] ?? null;
                $tokensUsed = $response->json()['usage']['total_tokens'] ?? 0;
                $status     = 'success';

                Log::info("AI Groq response received", [
                    'provider' => $provider->name,
                    'length'   => strlen($content ?? ''),
                    'tokens'   => $tokensUsed,
                    'ms'       => $responseTime,
                ]);

                // Perbarui statistik penggunaan provider
                $provider->increment('tokens_used_today', $tokensUsed);
                $provider->increment('tokens_used_month', $tokensUsed);
                $provider->increment('request_count_24h');
                $provider->update(['last_used_at' => now()]);

                // Simpan log penggunaan
                try {
                    AiUsageLog::create([
                        'provider_id'      => $provider->id,
                        'tokens_used'      => $tokensUsed,
                        'request_type'     => 'analyze_report',
                        'response_time_ms' => $responseTime,
                        'status'           => 'success',
                    ]);
                } catch (\Exception $e) {
                    // Abaikan error log usage agar tidak mengganggu alur utama
                }

                return $content;
            }

            // Tandai provider exhausted jika quota habis (HTTP 429)
            if ($response->status() === 429) {
                $provider->update(['status' => 'exhausted']);
                Log::warning("AI Service: Provider [{$provider->name}] quota exhausted (429)");
            }

            $errorMessage = substr($response->body(), 0, 300);
            Log::warning("Groq API error: " . $response->status() . " - " . $errorMessage);

        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);
            $errorMessage = $e->getMessage();
            Log::error("Groq call error: " . $errorMessage);
        }

        // Simpan log error penggunaan
        try {
            AiUsageLog::create([
                'provider_id'      => $provider->id,
                'tokens_used'      => $tokensUsed,
                'request_type'     => 'analyze_report',
                'response_time_ms' => $responseTime,
                'status'           => $status,
                'error_message'    => $errorMessage,
            ]);
        } catch (\Exception $e) {
            // Abaikan
        }

        return null;
    }

    /**
     * Hapus markdown code fence yang mungkin membungkus respons Groq.
     *
     * @param string $raw Respons mentah dari Groq
     * @return string Konten JSON yang sudah bersih
     */
    protected function stripJsonFence(string $raw): string
    {
        $cleaned = trim($raw);

        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
        $cleaned = preg_replace('/\s*```\s*$/i', '', $cleaned);

        return trim($cleaned);
    }

    /**
     * Ambil provider AI terbaik yang tersedia (status healthy, masih ada kuota).
     *
     * Urutan seleksi berdasarkan kolom priority (ASC).
     * Provider dilewati jika kuota harian atau bulanan sudah habis.
     *
     * @return AiProvider|null Provider yang siap digunakan, atau null jika semua habis/tidak sehat
     */
    protected function getBestProvider(): ?AiProvider
    {
        $providers = AiProvider::healthy()
            ->byPriority()
            ->get();

        foreach ($providers as $provider) {
            if ($provider->daily_token_limit > 0 && $provider->tokens_used_today >= $provider->daily_token_limit) {
                continue;
            }
            if ($provider->monthly_token_limit > 0 && $provider->tokens_used_month >= $provider->monthly_token_limit) {
                continue;
            }

            return $provider;
        }

        return null;
    }
}