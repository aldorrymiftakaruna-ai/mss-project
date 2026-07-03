<?php

namespace App\Services;

use App\Services\Traits\AiKeywordParserTrait;
use App\Services\Traits\AiProviderCallerTrait;

class AiService
{
    use AiKeywordParserTrait;
    use AiProviderCallerTrait;

    /**
     * Analisis teks laporan maintenance untuk mendeteksi asset dan tipe laporan.
     *
     * Alur:
     *   1. Parsing durasi dan root cause (selalu dijalankan, tidak bergantung provider)
     *   2. Coba analisis via AI provider (Groq/LLM)
     *   3. Fallback ke keyword matching jika provider tidak tersedia atau gagal
     *
     * @param string $text Teks laporan dari teknisi
     * @return array
     */
    public function analyzeReportText(string $text): array
    {
        $result = [
            'report_type'             => 'general',
            'suggested_asset'         => null,
            'detected_asset_id'       => null,
            'confidence'              => 0,
            'message'                 => '',
            'needs_clarification'     => false,
            'clarification_questions' => [],
            'parsed_duration_minutes' => null,
            'parsed_root_cause'       => null,
        ];

        // Parsing durasi pekerjaan & root cause dari teks awal.
        // Dijalankan terpisah dari deteksi asset di bawah, tidak bergantung
        // pada AI provider, supaya tetap berfungsi walau provider sedang down.
        $result['parsed_duration_minutes'] = $this->parseWorkDurationMinutes($text);
        $result['parsed_root_cause']       = $this->parseRootCauseHint($text);

        // 1. Coba AI provider (Groq/LLM)
        $aiResult = $this->analyzeWithAi($text);
        if ($aiResult !== null) {
            return array_merge($result, $aiResult);
        }

        // 2. Fallback: keyword matching
        $kwResult = $this->analyzeWithKeywords($text);
        return array_merge($result, $kwResult);
    }
}