<?php

namespace App\Services\Traits;

use App\Models\AiAlias;
use App\Models\Asset;

/**
 * Trait AiKeywordParserTrait
 *
 * Digunakan oleh AiService sebagai fallback ketika provider AI tidak tersedia.
 *
 * Method yang tersedia:
 *   - analyzeWithKeywords()         : Analisis teks laporan dengan keyword matching
 *   - detectAssetByTagNo()          : Cari asset berdasarkan tag_no atau description
 *   - detectReportType()            : Tentukan tipe laporan dari kata kunci
 *   - checkAliases()                : Cek alias yang sudah dipelajari dari teks
 *   - parseWorkDurationMinutes()    : Ekstrak durasi pekerjaan dari teks (dalam menit)
 *   - parseRootCauseHint()          : Ekstrak potongan kalimat penyebab dari teks
 */
trait AiKeywordParserTrait
{
    /**
     * Fallback: analisis teks laporan menggunakan keyword matching.
     *
     * Prioritas: tag_no/description -> alias.
     *
     * @param string $text Teks laporan yang dianalisis
     * @return array Hasil analisis
     */
    protected function analyzeWithKeywords(string $text): array
    {
        $result = [
            'report_type'             => 'general',
            'detected_asset'          => null,
            'suggested_asset'         => null,
            'detected_asset_id'       => null,
            'confidence'              => 0,
            'needs_clarification'     => false,
            'clarification_questions' => [],
            'message'                 => '',
        ];

        $result['report_type'] = $this->detectReportType($text);

        // 1. Cari asset berdasarkan tag_no atau description
        $detectedAsset = $this->detectAssetByTagNo($text);
        if ($detectedAsset) {
            $result['detected_asset']    = $detectedAsset['tag_no'];
            $result['suggested_asset']   = $detectedAsset['tag_no'];
            $result['detected_asset_id'] = $detectedAsset['id'];
            $result['confidence']       += 60;
        }

        // 2. Cek alias (hanya jika confidence masih rendah)
        if ($result['confidence'] < 60) {
            $aliasMatches = $this->checkAliases($text);
            if (!empty($aliasMatches['asset'])) {
                $aliasAsset = $aliasMatches['asset']->asset;
                if ($aliasAsset) {
                    $result['detected_asset']    = $aliasAsset->tag_no;
                    $result['suggested_asset']   = $aliasAsset->tag_no;
                    $result['detected_asset_id'] = $aliasAsset->id;
                    $result['confidence']        = max($result['confidence'], (int) ($aliasMatches['asset']->confidence * 100));
                }
            }
        }

        // 3. Tentukan apakah perlu klarifikasi berdasarkan confidence akhir
        if ($result['confidence'] >= 60) {
            $result['needs_clarification'] = false;
            $result['message']             = 'Laporan diterima. Asset terdeteksi.';
        } elseif ($result['confidence'] >= 20) {
            $result['needs_clarification'] = false;
            $result['message']             = 'Laporan diterima. Sebagian informasi terdeteksi.';
        } else {
            $result['needs_clarification'] = true;
            $result['message']             = 'Informasi kurang jelas. Silakan pilih asset/area kerja.';
        }

        return $result;
    }

    /**
     * Cari asset berdasarkan tag_no atau description dari teks.
     *
     * Melakukan pencarian LIKE pada kolom tag_no dan description,
     * dengan prioritas: exact match tag_no > partial tag_no > description.
     *
     * @param string $text Teks laporan
     * @return array|null Array ['id', 'tag_no'] atau null jika tidak ditemukan
     */
    protected function detectAssetByTagNo(string $text): ?array
    {
        $text = strtoupper(trim($text));

        // Ekstrak kode potensial dari teks
        preg_match_all('/[A-Z0-9][A-Z0-9\.\-\/]+[A-Z0-9]|[A-Z0-9]{2,}/i', $text, $matches);
        $words = array_unique($matches[0]);

        // Filter kata yang relevan (min 2 karakter, max 30)
        $candidates = array_filter($words, fn($w) => strlen($w) >= 2 && strlen($w) <= 30);

        // Urutkan: kata lebih panjang diprioritaskan
        usort($candidates, fn($a, $b) => strlen($b) <=> strlen($a));

        // PRIORITAS 1: Exact match tag_no
        foreach ($candidates as $candidate) {
            $asset = Asset::where('tag_no', $candidate)->first();
            if ($asset) {
                return ['tag_no' => $asset->tag_no, 'id' => $asset->id];
            }
        }

        // PRIORITAS 2: Partial match tag_no (LIKE %keyword%)
        foreach ($candidates as $candidate) {
            $clean = strtoupper(preg_replace('/[^A-Z0-9]/', '', $candidate));
            if (strlen($clean) < 2) continue;

            $asset = Asset::where('tag_no', 'like', "%{$clean}%")->first();
            if ($asset) {
                return ['tag_no' => $asset->tag_no, 'id' => $asset->id];
            }
        }

        // PRIORITAS 3: Partial match description (LIKE %keyword%)
        foreach ($candidates as $candidate) {
            $clean = strtoupper(preg_replace('/[^A-Z0-9]/', '', $candidate));
            if (strlen($clean) < 2) continue;

            $asset = Asset::where('description', 'like', "%{$clean}%")->first();
            if ($asset) {
                return ['tag_no' => $asset->tag_no, 'id' => $asset->id];
            }
        }

        return null;
    }

    /**
     * Deteksi tipe laporan dari teks berdasarkan kata kunci.
     *
     * @param string $text Teks laporan
     * @return string 'equipment_repair' | 'area_work' | 'general'
     */
    protected function detectReportType(string $text): string
    {
        $textLower = strtolower(trim($text));

        $equipmentKeywords = [
            'pompa', 'motor', 'panel', 'valve', 'sensor', 'transmitter',
            'switch', 'lampu', 'ac', 'kompresor', 'bearing', 'pully',
            'belt', 'coupling', 'seal', 'gasket', 'pipa', 'tangki',
            'heater', 'cooler', 'exchanger', 'filter', 'hydrant',
            'apar', 'grounding', 'kabel', 'relay', 'kontaktor',
            'genset', 'blower', 'agitator', 'mixer', 'conveyor',
            'trafo', 'inverter', 'vfd', 'flowmeter', 'level switch',
            'temperature', 'pressure', 'gauge', 'thermocouple',
            'solenoid', 'cylinder', 'actuator', 'positioner',
        ];

        $areaKeywords = [
            'kebersihan', 'pengecatan', 'bangunan', 'atap', 'lantai',
            'dinding', 'pagar', 'jalan', 'selokan', 'drainase',
            'taman', 'rumput', 'penerangan area', 'perbaikan area',
            'pekerjaan area',
        ];

        foreach ($equipmentKeywords as $kw) {
            if (str_contains($textLower, $kw)) {
                return 'equipment_repair';
            }
        }

        foreach ($areaKeywords as $kw) {
            if (str_contains($textLower, $kw)) {
                return 'area_work';
            }
        }

        return 'general';
    }

    /**
     * Cek apakah teks mengandung alias yang sudah dipelajari.
     *
     * Hanya alias dengan status 'confirmed' atau 'pending' yang diikutsertakan.
     * Jika alias cocok, usage_count di-increment otomatis.
     *
     * @param string $text Teks laporan
     * @return array Array berisi kunci 'asset' (bisa null atau objek AiAlias)
     */
    protected function checkAliases(string $text): array
    {
        $result    = ['asset' => null];
        $textUpper = strtoupper(trim($text));

        $aliases = AiAlias::whereIn('status', ['confirmed', 'pending'])
            ->whereNotNull('asset_id')
            ->with('asset')
            ->get();

        foreach ($aliases as $alias) {
            $aliasText = strtoupper($alias->alias_text);

            if (str_contains($textUpper, $aliasText)) {
                $result['asset'] = $alias;
                $alias->increment('usage_count');
            }
        }

        return $result;
    }

    /**
     * Coba ekstrak estimasi durasi pekerjaan (dalam menit) dari teks awal.
     *
     * Pola yang dikenali: "2 jam", "1,5 jam", "90 menit", "2 jam 30 menit".
     *
     * @param string $text Teks laporan
     * @return int|null Total menit yang diekstrak, atau null jika tidak ditemukan
     */
    protected function parseWorkDurationMinutes(string $text): ?int
    {
        $textLower    = strtolower($text);
        $totalMinutes = 0;
        $found        = false;

        // Pola "X jam" (X boleh desimal pakai koma atau titik)
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*jam/', $textLower, $m)) {
            $hours = (float) str_replace(',', '.', $m[1]);
            $totalMinutes += (int) round($hours * 60);
            $found = true;
        }

        // Pola "Y menit" — dijumlahkan dengan jam jika keduanya disebut
        if (preg_match('/(\d+)\s*menit/', $textLower, $m)) {
            $totalMinutes += (int) $m[1];
            $found = true;
        }

        return $found ? $totalMinutes : null;
    }

    /**
     * Coba ekstrak potongan kalimat yang mengindikasikan root cause dari teks awal.
     *
     * Pola: "karena ...", "akibat ...", "disebabkan ...", "penyebab ..."
     *
     * @param string $text Teks laporan
     * @return string|null Potongan kalimat root cause, atau null jika tidak ditemukan
     */
    protected function parseRootCauseHint(string $text): ?string
    {
        $patterns = [
            '/\bkarena\s+(.+)/i',
            '/\bakibat\s+(.+)/i',
            '/\bdisebabkan\s+(?:oleh\s+)?(.+)/i',
            '/\bpenyebab(?:nya)?\s*:?\s+(.+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $hint = trim($m[1]);
                $hint = trim(preg_split('/[.!?\n]/', $hint)[0]);

                if (mb_strlen($hint) >= 3) {
                    return $hint;
                }
            }
        }

        return null;
    }
}