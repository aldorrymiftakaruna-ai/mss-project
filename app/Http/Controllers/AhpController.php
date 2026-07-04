<?php

namespace App\Http\Controllers;

use App\Models\AhpCriterion;
use App\Models\AhpSession;
use App\Services\Prescriptive\AhpService;
use App\Services\Prescriptive\TopsisService;
use Exception;
use Illuminate\Http\Request;

class AhpController extends Controller
{
    protected AhpService $ahpService;
    protected TopsisService $topsisService;

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
        return view('prescriptive.ahp.create');
    }

    /**
     * Simpan sesi baru beserta kriteria.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'ahli_id'    => 'nullable|exists:employees,id',
            'criteria'   => 'required|array|min:2',
            'criteria.*' => 'required|string|max:255',
        ]);

        $session = $this->ahpService->createSession($request->name, $request->ahli_id);

        foreach ($request->criteria as $criterionName) {
            $this->ahpService->addCriterion($session->id, $criterionName);
        }

        return redirect()->route('ahp.pairwise', $session->id)
            ->with('success', 'Sesi AHP dibuat. Silakan isi perbandingan berpasangan.');
    }

    /**
     * Tampilkan form pairwise.
     */
    public function pairwise(AhpSession $session)
    {
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
    public function storePairwise(Request $request, AhpSession $session)
    {
        $request->validate([
            'pairwise' => 'required|array',
        ]);

        $criteria = $session->criteria()->orderBy('id')->pluck('id');

        foreach ($request->pairwise as $key => $value) {
            // Format key: "a_b" → criterion_a_id, criterion_b_id
            $parts = explode('_', $key);
            if (count($parts) !== 2) continue;

            [$aId, $bId] = $parts;

            // Lewati jika A == B atau nilai kosong
            if ($aId === $bId || $value === null || $value === '') continue;

            $this->ahpService->setPairwise(
                $session->id,
                (int) $aId,
                (int) $bId,
                (float) $value
            );
        }

        // Hitung langsung
        try {
            $result = $this->ahpService->calculateFull($session);

            return redirect()->route('ahp.result', $session->id)
                ->with('success', 'Perbandingan disimpan. Hasil AHP: CR = ' . $result['consistency']['cr']);
        } catch (Exception $e) {
            return redirect()->route('ahp.pairwise', $session->id)
                ->with('error', 'Gagal menghitung AHP: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan hasil AHP.
     */
    public function result(AhpSession $session)
    {
        $result = $this->ahpService->getResult($session);
        return view('prescriptive.ahp.result', compact('session', 'result'));
    }

    /**
     * Hapus sesi AHP.
     */
    public function destroy(AhpSession $session)
    {
        $session->delete();
        return redirect()->route('ahp.index')
            ->with('success', 'Sesi AHP berhasil dihapus.');
    }

    /**
     * Hitung ranking TOPSIS untuk sesi tertentu.
     */
    public function ranking(AhpSession $session)
    {
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
