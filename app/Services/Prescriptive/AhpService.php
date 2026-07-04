<?php

namespace App\Services\Prescriptive;

use App\Models\AhpCriterion;
use App\Models\AhpPairwise;
use App\Models\AhpSession;
use Exception;

class AhpService
{
    /**
     * Skala Saaty 1-9 beserta labels untuk referensi.
     */
    public const SAATY_SCALE = [
        1 => 'Sama penting',
        2 => 'Mendekati sedikit lebih penting',
        3 => 'Sedikit lebih penting',
        4 => 'Mendekati lebih penting',
        5 => 'Lebih penting',
        6 => 'Mendekati sangat penting',
        7 => 'Sangat penting',
        8 => 'Mendekati mutlak',
        9 => 'Mutlak lebih penting',
    ];

    /**
     * Random Index (RI) untuk matriks ukuran 1-15.
     */
    public const RI = [
        1  => 0.00,
        2  => 0.00,
        3  => 0.58,
        4  => 0.90,
        5  => 1.12,
        6  => 1.24,
        7  => 1.32,
        8  => 1.41,
        9  => 1.45,
        10 => 1.49,
        11 => 1.51,
        12 => 1.48,
        13 => 1.56,
        14 => 1.57,
        15 => 1.59,
    ];

    /**
     * Membuat sesi AHP baru.
     *
     * @param string $name Nama sesi
     * @param int|null $ahliId ID employee sebagai ahli
     * @return AhpSession
     */
    public function createSession(string $name, ?int $ahliId = null): AhpSession
    {
        return AhpSession::create([
            'name'    => $name,
            'ahli_id' => $ahliId,
        ]);
    }

    /**
     * Menambahkan kriteria ke sesi AHP.
     *
     * @param int $sessionId
     * @param string $name Nama internal kriteria
     * @param string|null $label Label tampilan
     * @return AhpCriterion
     */
    public function addCriterion(int $sessionId, string $name, ?string $label = null): AhpCriterion
    {
        return AhpCriterion::create([
            'ahp_session_id' => $sessionId,
            'name'           => $name,
            'label'          => $label ?? $name,
        ]);
    }

    /**
     * Menambahkan atau memperbarui perbandingan pairwise antara dua kriteria.
     *
     * Nilai menggunakan skala Saaty 1-9.
     * Jika nilai < 1, secara otomatis dibalik (1/nilai) dan posisi A/B ditukar.
     *
     * @param int $sessionId
     * @param int $criterionAId
     * @param int $criterionBId
     * @param float $value Skala Saaty (1-9)
     * @return AhpPairwise
     *
     * @throws Exception Jika nilai di luar rentang 1-9 (setelah penanganan reciprocal)
     */
    public function setPairwise(int $sessionId, int $criterionAId, int $criterionBId, float $value): AhpPairwise
    {
        // Jika A == B, nilai harus 1
        if ($criterionAId === $criterionBId) {
            $value = 1;
        }

        // Pastikan nilai dalam skala 1-9
        $absValue = abs($value);

        if ($absValue < 1 || $absValue > 9) {
            throw new Exception("Nilai pairwise harus antara 1 sampai 9. Diberikan: {$value}");
        }

        // Simpan dengan nilai positif. Nilai reciprocal di-handle saat transposing matriks.
        return AhpPairwise::updateOrCreate(
            [
                'ahp_session_id' => $sessionId,
                'criterion_a_id' => $criterionAId,
                'criterion_b_id' => $criterionBId,
            ],
            [
                'value' => $absValue,
            ]
        );
    }

    /**
     * Mendapatkan matriks pairwise lengkap untuk sesi tertentu.
     *
     * @param AhpSession $session
     * @return array Matriks 2D [i][j] = nilai perbandingan
     */
    public function getPairwiseMatrix(AhpSession $session): array
    {
        $criteria = $session->criteria()->orderBy('id')->get();
        $count    = $criteria->count();

        if ($count < 2) {
            return [];
        }

        $criteriaIds = $criteria->pluck('id')->toArray();
        $indexMap    = array_flip($criteriaIds); // id => index (0-based)

        // Inisialisasi matriks identitas
        $matrix = [];
        for ($i = 0; $i < $count; $i++) {
            $matrix[$i] = array_fill(0, $count, 1);
        }

        // Isi dari pairwise yang tersimpan
        $pairwise = $session->pairwise()->get();

        foreach ($pairwise as $p) {
            $i = $indexMap[$p->criterion_a_id] ?? null;
            $j = $indexMap[$p->criterion_b_id] ?? null;

            if ($i === null || $j === null || $i === $j) {
                continue;
            }

            $matrix[$i][$j] = (float) $p->value;
            // Reciprocal: matrix[j][i] = 1 / value
            $matrix[$j][$i] = 1 / (float) $p->value;
        }

        return $matrix;
    }

    /**
     * Menghitung bobot kriteria menggunakan metode eigenvector (rata-rata baris ternormalisasi).
     *
     * Langkah:
     * 1. Normalisasi matriks (bagi setiap kolom dengan jumlah kolom)
     * 2. Rata-rata setiap baris → priority vector / bobot
     *
     * @param array $matrix Matriks pairwise 2D
     * @return array [weights => [index => bobot], priorityVector => [index => bobot], n => jumlah kriteria]
     */
    public function calculateWeights(array $matrix): array
    {
        $n = count($matrix);

        if ($n === 0) {
            return ['weights' => [], 'priority_vector' => [], 'n' => 0];
        }

        // Hitung jumlah setiap kolom
        $columnSums = array_fill(0, $n, 0);
        for ($j = 0; $j < $n; $j++) {
            for ($i = 0; $i < $n; $i++) {
                $columnSums[$j] += $matrix[$i][$j];
            }
        }

        // Normalisasi: setiap elemen dibagi jumlah kolomnya
        $normalized = [];
        for ($i = 0; $i < $n; $i++) {
            $normalized[$i] = array_fill(0, $n, 0);
            for ($j = 0; $j < $n; $j++) {
                if ($columnSums[$j] > 0) {
                    $normalized[$i][$j] = $matrix[$i][$j] / $columnSums[$j];
                }
            }
        }

        // Priority vector: rata-rata baris
        $priorityVector = [];
        for ($i = 0; $i < $n; $i++) {
            $priorityVector[$i] = array_sum($normalized[$i]) / $n;
        }

        // Bobot (sama dengan priority vector)
        $weights = $priorityVector;

        return [
            'weights'         => $weights,
            'priority_vector' => $priorityVector,
            'n'               => $n,
        ];
    }

    /**
     * Menghitung Consistency Ratio (CR).
     *
     * Langkah:
     * 1. Kalikan matriks pairwise dengan priority vector → weighted sum vector
     * 2. Bagi weighted sum dengan priority vector → consistency vector
     * 3. Lambda max = rata-rata consistency vector
     * 4. CI = (lambda max - n) / (n - 1)
     * 5. CR = CI / RI
     *
     * @param array $matrix Matriks pairwise 2D
     * @param array $priorityVector Bobot/priority vector
     * @return array [lambdaMax, ci, ri, cr, isConsistent]
     */
    public function calculateConsistencyRatio(array $matrix, array $priorityVector): array
    {
        $n = count($matrix);

        if ($n < 2) {
            return [
                'lambda_max'   => 1,
                'ci'           => 0,
                'ri'           => 0,
                'cr'           => 0,
                'is_consistent' => true,
            ];
        }

        // 1. Weighted sum vector = matrix * priorityVector
        $weightedSum = [];
        for ($i = 0; $i < $n; $i++) {
            $sum = 0;
            for ($j = 0; $j < $n; $j++) {
                $sum += $matrix[$i][$j] * $priorityVector[$j];
            }
            $weightedSum[$i] = $sum;
        }

        // 2. Consistency vector = weightedSum / priorityVector
        $consistencyVector = [];
        for ($i = 0; $i < $n; $i++) {
            $consistencyVector[$i] = $priorityVector[$i] > 0
                ? $weightedSum[$i] / $priorityVector[$i]
                : 0;
        }

        // 3. Lambda max
        $lambdaMax = array_sum($consistencyVector) / $n;

        // 4. Consistency Index (CI)
        $ci = ($lambdaMax - $n) / ($n - 1);

        // 5. Random Index (RI)
        $ri = self::RI[$n] ?? 0;

        // 6. Consistency Ratio (CR)
        $cr = $ri > 0 ? $ci / $ri : 0;

        // Konsisten jika CR <= 0.1 (10%)
        $isConsistent = $cr <= 0.1;

        return [
            'lambda_max'    => round($lambdaMax, 5),
            'ci'            => round($ci, 5),
            'ri'            => $ri,
            'cr'            => round($cr, 5),
            'is_consistent' => $isConsistent,
        ];
    }

    /**
     * Menyimpan bobot dan CR ke database (tabel criteria dan session).
     *
     * @param AhpSession $session
     * @param array $weights Bobot per index (urutan sesuai ID criteria ASC)
     * @param array $consistency Hasil dari calculateConsistencyRatio
     * @return void
     */
    public function persistResult(AhpSession $session, array $weights, array $consistency): void
    {
        $criteria = $session->criteria()->orderBy('id')->get();

        foreach ($criteria as $index => $criterion) {
            $weight = $weights[$index] ?? 0;
            $criterion->update([
                'weight'          => round($weight, 5),
                'priority_vector' => round($weight, 5),
            ]);
        }

        $session->update([
            'consistency_ratio' => $consistency['cr'],
            'is_final'          => $consistency['is_consistent'],
        ]);
    }

    /**
     * Menjalankan perhitungan AHP penuh untuk satu sesi.
     *
     * @param AhpSession|int $session Instance atau ID sesi
     * @return array Hasil lengkap: matrix, weights, consistency, criteria
     *
     * @throws Exception Jika kriteria < 2
     */
    public function calculateFull(AhpSession|int $session): array
    {
        if (is_int($session)) {
            $session = AhpSession::findOrFail($session);
        }

        $criteria = $session->criteria()->orderBy('id')->get();

        if ($criteria->count() < 2) {
            throw new Exception('Minimal 2 kriteria diperlukan untuk perhitungan AHP.');
        }

        // Dapatkan matriks pairwise
        $matrix = $this->getPairwiseMatrix($session);

        // Hitung bobot
        $weightResult = $this->calculateWeights($matrix);

        // Hitung CR
        $consistency = $this->calculateConsistencyRatio($matrix, $weightResult['priority_vector']);

        // Simpan ke database
        $this->persistResult($session, $weightResult['weights'], $consistency);

        // Siapkan output per kriteria
        $criteriaResult = [];
        foreach ($criteria as $index => $c) {
            $criteriaResult[] = [
                'id'              => $c->id,
                'name'            => $c->name,
                'label'           => $c->label,
                'weight'          => round($weightResult['weights'][$index] ?? 0, 5),
                'priority_vector' => round($weightResult['priority_vector'][$index] ?? 0, 5),
            ];
        }

        return [
            'session'     => [
                'id'   => $session->id,
                'name' => $session->name,
            ],
            'matrix'      => $matrix,
            'criteria'    => $criteriaResult,
            'weights'     => $weightResult['weights'],
            'consistency' => $consistency,
            'n'           => $criteria->count(),
        ];
    }

    /**
     * Mendapatkan hasil AHP dari sesi yang sudah dihitung.
     *
     * @param AhpSession $session
     * @return array
     */
    public function getResult(AhpSession $session): array
    {
        $criteria = $session->criteria()->orderBy('id')->get();

        $criteriaResult = [];
        foreach ($criteria as $c) {
            $criteriaResult[] = [
                'id'              => $c->id,
                'name'            => $c->name,
                'label'           => $c->label,
                'weight'          => (float) $c->weight,
                'priority_vector' => (float) $c->priority_vector,
            ];
        }

        // Bangun ulang matriks dari data yang ada
        $matrix = $this->getPairwiseMatrix($session);

        return [
            'session'     => [
                'id'                => $session->id,
                'name'              => $session->name,
                'is_final'          => $session->is_final,
                'consistency_ratio' => (float) $session->consistency_ratio,
            ],
            'criteria'    => $criteriaResult,
            'matrix'      => $matrix,
            'consistency' => [
                'cr' => (float) $session->consistency_ratio,
            ],
            'n'           => $criteria->count(),
        ];
    }

    /**
     * Mendapatkan daftar sesi (history).
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHistory(int $limit = 20)
    {
        return AhpSession::with('criteria')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
