<?php

namespace App\Http\Controllers;

use App\Models\AhpCriterion;
use App\Models\AhpSession;
use App\Services\Prescriptive\AhpService;
use App\Services\Prescriptive\TopsisService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AhpController extends Controller
{
    protected AhpService $ahpService;
    protected TopsisService $topsisService;

    /**
     * Daftar kriteria valid yang didukung sistem TOPSIS.
     * key = value internal (disimpan ke DB name), value = label human-readable.
     */
    public const VALID_CRITERIA = [
        'cm_findings'          => 'Frekuensi Temuan CM',
        'avg_severity'         => 'Tingkat Keparahan Temuan CM',
        'cm_status'            => 'Status Terakhir CM (Normal/Alarm/Danger)',
        'cm_alarm_danger_count'=> 'Frekuensi Alarm/Danger pada CM',
        'downtime'             => 'Total Downtime',
        'report_count'         => 'Jumlah Laporan Maintenance',
        'mtbf_days'            => 'MTBF (Mean Time Between Failures)',
    ];

    public function __construct(AhpService $ahpService, TopsisService $topsisService)
    {
        $this->ahpService = $ahpService;
        $this->topsisService = $topsisService;
    }

    /**
     * Daftar sesi AHP.
     */
    public function index()
    {
        $sessions = $this->ahpService->getHistory(20);
        return view('prescriptive.ahp.index', compact('sessions'));
    }

    /**
     * Form buat sesi baru + input kriteria.
     */
    public function create()
    {
        $validCriteria = self::VALID_CRITERIA;
        return view('prescriptive.ahp.create', compact('validCriteria'));
    }

    /**
     * Simpan sesi baru beserta kriteria.
     */
    public function store(Request $request)
    {
        $validKeys = array_keys(self::VALID_CRITERIA);

        $request->validate([
            'name'       => 'required|string|max:255',
            'ahli_id'    => 'nullable|exists:employees,id',
            'criteria'   => 'required|array|min:2',
            'criteria.*' => ['required', 'string', Rule::in($validKeys)],
        ], [
            'criteria.*.in' => 'Kriteria tidak valid. Pilih dari daftar yang tersedia.',
        ]);

        // Cegah duplikat
        $criteriaNames = $request->criteria;
        if (count($criteriaNames) !== count(array_unique($criteriaNames))) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['criteria' => 'Tidak boleh ada kriteria yang sama dalam satu sesi.']);
        }

        $session = $this->ahpService->createSession($request->name, $request->ahli_id);

        foreach ($criteriaNames as $criterionName) {
            $label = self::VALID_CRITERIA[$criterionName] ?? $criterionName;
            $this->ahpService->addCriterion($session->id, $criterionName, $label);
        }

        return redirect()->route('ahp.pairwise', $session->id)
            ->with('success', 'Sesi AHP dibuat. Silakan isi perbandingan berpasangan.');
    }

    /**
     * Tampilkan form pairwise.
     */
    public function pairwise(AhpSession $ahpSession)
    {
        $session = $ahpSession;

        $criteria = $session->criteria()->orderBy('id')->get();

        if ($criteria->count() < 2) {
            return redirect()->route('ahp.index')
                ->with('error', 'Minimal 2 kriteria diperlukan untuk perbandingan.');
        }

        // Ambil matriks yang sudah ada
        $matrix = $this->ahpService->getPairwiseMatrix($session);

        // Skala Saaty untuk dropdown
        $saatyScale = AhpService::SAATY_SCALE;

        return view('prescriptive.ahp.pairwise', compact('session', 'criteria', 'matrix', 'saatyScale'));
    }

    /**
     * Simpan nilai pairwise.
     */
    public function storePairwise(Request $request, AhpSession $ahpSession)
    {
        $request->validate([
            'pairwise' => 'required|array',
        ]);
        $criteria = $ahpSession->criteria()->orderBy('id')->pluck('id');

        foreach ($request->pairwise as $key => $value) {
            // Format key: "a_b" → criterion_a_id, criterion_b_id
            $parts = explode('_', $key);
            if (count($parts) !== 2) continue;

            [$aId, $bId] = $parts;

            // Lewati jika A == B atau nilai kosong
            if ($aId === $bId || $value === null || $value === '') continue;

            $this->ahpService->setPairwise(
                $ahpSession->id,
                (int) $aId,
                (int) $bId,
                (float) $value
            );
        }

        // Hitung langsung
        try {
            $result = $this->ahpService->calculateFull($ahpSession);

            return redirect()->route('ahp.result', $ahpSession->id)
                ->with('success', 'Perbandingan disimpan. Hasil AHP: CR = ' . $result['consistency']['cr']);
        } catch (Exception $e) {
            return redirect()->route('ahp.pairwise', $ahpSession->id)
                ->with('error', 'Gagal menghitung AHP: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan hasil AHP.
     */
    public function result(AhpSession $ahpSession)
    {
        $session = $ahpSession;

        $result = $this->ahpService->getResult($session);
        return view('prescriptive.ahp.result', compact('session', 'result'));
    }

    /**
     * Hapus sesi AHP beserta seluruh data terkait.
     *
     * @param AhpSession $ahpSession Instance hasil route binding
     */
    public function destroy(AhpSession $ahpSession)
    {
        $session = $ahpSession;

        // Hapus pairwise terkait
        $session->pairwise()->delete();
        // Hapus criteria terkait
        $session->criteria()->delete();
        // Hapus sesi
        $session->delete();

        return redirect()->route('ahp.index')
            ->with('success', 'Sesi AHP berhasil dihapus.');
    }

    /**
     * Hitung ranking TOPSIS untuk sesi tertentu.
     *
     * @param AhpSession $ahpSession Instance hasil route binding
     */
    public function ranking(AhpSession $ahpSession)
    {
        $session = $ahpSession;

        $criteria = $session->criteria()->orderBy('id')->get();

        if ($criteria->isEmpty()) {
            return redirect()->route('ahp.index')
                ->with('error', 'Sesi belum memiliki kriteria.');
        }

        try {
            $result = $this->topsisService->calculateRanking($session);
            return view('prescriptive.topsis.ranking', compact('session', 'result'));
        } catch (Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghitung TOPSIS: ' . $e->getMessage());
        }
    }
}

