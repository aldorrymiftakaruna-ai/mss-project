<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;
use App\Models\Asset;
use App\Models\CmMeasurement;
use App\Models\CmFinding;
use App\Models\Employee;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CmController extends Controller
{
    public function index()
    {
        $cms       = CmMeasurement::with('asset.company')->latest('tanggal')->get();
        $findings  = CmFinding::with(['asset.company', 'reporter', 'pic'])->latest('tanggal')->get();
        $assets    = Asset::with('company')->orderBy('tag_no')->get();
        $employees = Employee::orderBy('name')->get();

        // ── Dashboard Aggregations ──────────────────────────────
        $now = Carbon::now();

        // ✅ Ambil tanggal terbaru dari data, bukan dari now()
        $latestDataDate = CmMeasurement::max('tanggal');
        $refDate = $latestDataDate ? Carbon::parse($latestDataDate) : $now;

        // Default: 12 bulan terakhir (atau kurang jika data belum 12 bulan)
        $monthsToShow = 12;
        $startDate = $refDate->copy()->subMonths($monthsToShow - 1)->startOfMonth();

        $recentCms = CmMeasurement::with('asset')
            ->where('tanggal', '>=', $startDate)
            ->latest('tanggal')
            ->get();

        $totalRecords = $recentCms->count();
        $alarmCount = 0;
        $dangerCount = 0;
        $goodCount = 0;

        foreach ($recentCms as $cm) {
            $asset = $cm->asset;
            if (!$asset) continue;
            $status = $asset->calcStatusFromCm($cm);
            if ($status === 'danger') $dangerCount++;
            elseif ($status === 'alarm') $alarmCount++;
            else $goodCount++;
        }

        // ── Donut per PT ──
        $latestMonth = $recentCms->isNotEmpty()
            ? $recentCms->max('tanggal')?->format('Y-m')
            : $refDate->format('Y-m');

        $cmsPerCompany = Company::with(['assets.cmMeasurements' => function ($q) use ($latestMonth) {
            $q->whereYear('tanggal', (int)substr($latestMonth, 0, 4))
              ->whereMonth('tanggal', (int)substr($latestMonth, 5, 2));
        }])->get()->map(function ($company) {
            $good = 0; $alarm = 0; $danger = 0;
            foreach ($company->assets as $asset) {
                foreach ($asset->cmMeasurements as $cm) {
                    $st = $asset->calcStatusFromCm($cm);
                    if ($st === 'danger') $danger++;
                    elseif ($st === 'alarm') $alarm++;
                    else $good++;
                }
            }
            $total = $good + $alarm + $danger;
            return [
                'company' => $company,
                'good' => $good,
                'alarm' => $alarm,
                'danger' => $danger,
                'total' => $total,
            ];
        })->filter(fn($c) => $c['total'] > 0)->values();

        // ── Stacked Bar (12 bulan) ──
        $months = [];
        for ($i = $monthsToShow - 1; $i >= 0; $i--) {
            $m = $refDate->copy()->subMonths($i);
            $months[] = $m->format('Y-m');
        }
        $barLabels = array_map(fn($m) => Carbon::createFromFormat('Y-m', $m)->format('M Y'), $months);
        $barGood = $barAlarm = $barDanger = [];

        foreach ($months as $ym) {
            $g = 0; $a = 0; $d = 0;
            foreach ($recentCms as $cm) {
                if ($cm->tanggal->format('Y-m') !== $ym) continue;
                $asset = $cm->asset;
                if (!$asset) continue;
                $st = $asset->calcStatusFromCm($cm);
                if ($st === 'danger') $d++;
                elseif ($st === 'alarm') $a++;
                else $g++;
            }
            $barGood[] = $g;
            $barAlarm[] = $a;
            $barDanger[] = $d;
        }

        // ── Top 10 Vibrasi ──
        $latestMonthCms = $recentCms->filter(fn($cm) => $cm->tanggal->format('Y-m') === $latestMonth);

        $topVib = $latestMonthCms->map(function ($cm) {
            $asset = $cm->asset;
            $vibFields = [
                $cm->driver_de_vib_v, $cm->driver_de_vib_h, $cm->driver_de_vib_a,
                $cm->driver_nde_vib_v, $cm->driver_nde_vib_h, $cm->driver_nde_vib_a,
                $cm->driven_de_vib_v, $cm->driven_de_vib_h, $cm->driven_de_vib_a,
                $cm->driven_nde_vib_v, $cm->driven_nde_vib_h, $cm->driven_nde_vib_a,
            ];
            $maxVib = collect($vibFields)->filter()->map(fn($v) => (float)$v)->max() ?? 0;
            $status = $asset ? $asset->calcStatusFromCm($cm) : 'normal';
            return [
                'asset_tag' => $asset->tag_no ?? '—',
                'company_code' => $asset->company->code ?? '—',
                'asset_desc' => $asset->description ?? '—',
                'max_vib' => round($maxVib, 2),
                'status' => $status,
            ];
        })->sortByDesc('max_vib')->take(10)->values();

        $topMaxVib = $topVib->isNotEmpty() ? $topVib->max('max_vib') : 0;
        $latestMonthLabel = Carbon::createFromFormat('Y-m', $latestMonth)->format('F Y');
        $lastUpdated = CmMeasurement::max('created_at');

        return view('cm.index', compact(
            'cms', 'findings', 'assets', 'employees',
            'totalRecords', 'alarmCount', 'dangerCount', 'goodCount',
            'cmsPerCompany', 'latestMonthLabel',
            'barLabels', 'barGood', 'barAlarm', 'barDanger',
            'topVib', 'topMaxVib', 'lastUpdated'
        ));
    }

    /**
     * AJAX endpoint untuk filter trend chart berdasarkan range bulan.
     */
    public function trendData(Request $request)
    {
        $now = Carbon::now();
        $latestDataDate = CmMeasurement::max('tanggal');
        $refDate = $latestDataDate ? Carbon::parse($latestDataDate) : $now;

        // Validasi range: bisa "12", "6", "3", atau custom "start" + "end"
        $range = $request->input('range', '12');
        $startMonth = $request->input('start'); // format YYYY-MM
        $endMonth = $request->input('end');     // format YYYY-MM

        if (in_array($range, ['3', '6', '12'])) {
            $monthsCount = (int) $range;
            $startDate = $refDate->copy()->subMonths($monthsCount - 1)->startOfMonth();
        } elseif ($startMonth && $endMonth) {
            // Validasi format YYYY-MM
            if (!preg_match('/^\d{4}-\d{2}$/', $startMonth) || !preg_match('/^\d{4}-\d{2}$/', $endMonth)) {
                return response()->json(['error' => 'Format rentang tanggal tidak valid.'], 422);
            }
            $startDate = Carbon::createFromFormat('Y-m', $startMonth)->startOfMonth();
            $endDate = Carbon::createFromFormat('Y-m', $endMonth)->endOfMonth();
            if ($startDate->greaterThan($endDate)) {
                return response()->json(['error' => 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.'], 422);
            }
            $refDate = $endDate;
        } else {
            return response()->json(['error' => 'Parameter range tidak valid.'], 422);
        }

        // Query CM dalam rentang
        $query = CmMeasurement::with('asset')->where('tanggal', '>=', $startDate);
        if (isset($endDate)) {
            $query->where('tanggal', '<=', $endDate);
        }
        $recentCms = $query->latest('tanggal')->get();

        // Hitung summary
        $totalRecords = $recentCms->count();
        $alarmCount = 0; $dangerCount = 0; $goodCount = 0;
        foreach ($recentCms as $cm) {
            $asset = $cm->asset;
            if (!$asset) continue;
            $status = $asset->calcStatusFromCm($cm);
            if ($status === 'danger') $dangerCount++;
            elseif ($status === 'alarm') $alarmCount++;
            else $goodCount++;
        }

        // Generate label bulan dari startDate sampai refDate
        $months = [];
        $cursor = $startDate->copy()->startOfMonth();
        $endCursor = isset($endDate) ? $endDate->copy()->startOfMonth() : $refDate->copy()->startOfMonth();
        while ($cursor->lessThanOrEqualTo($endCursor)) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $barLabels = array_map(fn($m) => Carbon::createFromFormat('Y-m', $m)->format('M Y'), $months);
        $barGood = $barAlarm = $barDanger = [];

        foreach ($months as $ym) {
            $g = 0; $a = 0; $d = 0;
            foreach ($recentCms as $cm) {
                if ($cm->tanggal->format('Y-m') !== $ym) continue;
                $asset = $cm->asset;
                if (!$asset) continue;
                $st = $asset->calcStatusFromCm($cm);
                if ($st === 'danger') $d++;
                elseif ($st === 'alarm') $a++;
                else $g++;
            }
            $barGood[] = $g;
            $barAlarm[] = $a;
            $barDanger[] = $d;
        }

        return response()->json([
            'labels'     => $barLabels,
            'good'       => $barGood,
            'alarm'      => $barAlarm,
            'danger'     => $barDanger,
            'total'      => $totalRecords,
        ]);
    }

    /**
     * Helper: simpan satu file foto temuan ke storage/app/public/cm_findings,
     * kembalikan path relatif untuk disimpan ke kolom foto_path / foto_path_2 / foto_path_3.
     */
    private function storeFindingPhoto(Request $request, string $inputName): ?string
    {
        if (!$request->hasFile($inputName)) {
            return null;
        }
        return $request->file($inputName)->store('cm_findings', 'public');
    }

    /**
     * Helper: hapus file lama dari storage (dipanggil saat update mengganti foto).
     */
    private function deleteOldPhoto(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function store(Request $request)
    {
        $type = $request->input('type', 'measurement');

        if ($type === 'finding') {
            $request->validate([
                'asset_id'    => 'required|exists:assets,id',
                'tanggal'     => 'required|date',
                'kategori'    => 'required|string',
                'severity'    => 'required|in:low,medium,high',
                'foto_1'      => 'nullable|image|max:4096',
                'foto_2'      => 'nullable|image|max:4096',
                'foto_3'      => 'nullable|image|max:4096',
                'date_action' => 'nullable|date',
                'pic_id'      => 'nullable|exists:employees,id',
            ]);

            CmFinding::create([
                'asset_id'    => $request->asset_id,
                'tanggal'     => $request->tanggal,
                'reported_by' => $request->reported_by ?: null,
                'kategori'    => $request->kategori,
                'severity'    => $request->severity,
                'deskripsi'   => $request->deskripsi,
                'status'      => $request->input('status', 'open'),
                'analysis'    => $request->analysis ?: null,
                'action'      => $request->action ?: null,
                'pic_id'      => $request->pic_id ?: null,
                'date_action' => $request->date_action ?: null,
                'remark'      => $request->remark ?: null,
                'foto_path'   => $this->storeFindingPhoto($request, 'foto_1'),
                'foto_path_2' => $this->storeFindingPhoto($request, 'foto_2'),
                'foto_path_3' => $this->storeFindingPhoto($request, 'foto_3'),
            ]);

            return redirect()->route('cm.index')
                ->with('success', 'Temuan visual berhasil disimpan.');
        }

        // type = measurement
        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'tanggal'  => 'required|date',
        ]);

        CmMeasurement::create([
            'asset_id'         => $request->asset_id,
            'tanggal'          => $request->tanggal,
            'measured_by'      => $request->measured_by ?: null,
            'driver_de_vib_v'  => $request->driver_de_vib_v ?: null,
            'driver_de_vib_h'  => $request->driver_de_vib_h ?: null,
            'driver_de_vib_a'  => $request->driver_de_vib_a ?: null,
            'driver_de_cf'     => $request->driver_de_cf ?: null,
            'driver_de_temp'   => $request->driver_de_temp ?: null,
            'driver_nde_vib_v' => $request->driver_nde_vib_v ?: null,
            'driver_nde_vib_h' => $request->driver_nde_vib_h ?: null,
            'driver_nde_vib_a' => $request->driver_nde_vib_a ?: null,
            'driver_nde_cf'    => $request->driver_nde_cf ?: null,
            'driver_nde_temp'  => $request->driver_nde_temp ?: null,
            'driver_ampere'    => $request->driver_ampere ?: null,
            'driven_de_vib_v'  => $request->driven_de_vib_v ?: null,
            'driven_de_vib_h'  => $request->driven_de_vib_h ?: null,
            'driven_de_vib_a'  => $request->driven_de_vib_a ?: null,
            'driven_de_cf'     => $request->driven_de_cf ?: null,
            'driven_de_temp'   => $request->driven_de_temp ?: null,
            'driven_nde_vib_v' => $request->driven_nde_vib_v ?: null,
            'driven_nde_vib_h' => $request->driven_nde_vib_h ?: null,
            'driven_nde_vib_a' => $request->driven_nde_vib_a ?: null,
            'driven_nde_cf'    => $request->driven_nde_cf ?: null,
            'driven_nde_temp'  => $request->driven_nde_temp ?: null,
            'catatan'          => $request->catatan,
        ]);

        $asset = Asset::find($request->asset_id);
        $asset->refreshStatus();

        return redirect()->route('cm.index')
            ->with('success', 'Data CM berhasil disimpan. Status equipment diperbarui.');
    }

    /**
     * Update data temuan visual yang sudah ada (dipanggil dari modal Edit).
     */
    public function updateFinding(Request $request, CmFinding $cmFinding)
    {
        $request->validate([
            'asset_id'    => 'required|exists:assets,id',
            'tanggal'     => 'required|date',
            'kategori'    => 'required|string',
            'severity'    => 'required|in:low,medium,high',
            'status'      => 'required|in:open,acknowledged,resolved',
            'foto_1'      => 'nullable|image|max:4096',
            'foto_2'      => 'nullable|image|max:4096',
            'foto_3'      => 'nullable|image|max:4096',
            'date_action' => 'nullable|date',
            'pic_id'      => 'nullable|exists:employees,id',
        ]);

        $data = [
            'asset_id'    => $request->asset_id,
            'tanggal'     => $request->tanggal,
            'reported_by' => $request->reported_by ?: null,
            'kategori'    => $request->kategori,
            'severity'    => $request->severity,
            'deskripsi'   => $request->deskripsi,
            'status'      => $request->status,
            'analysis'    => $request->analysis ?: null,
            'action'      => $request->action ?: null,
            'pic_id'      => $request->pic_id ?: null,
            'date_action' => $request->date_action ?: null,
            'remark'      => $request->remark ?: null,
        ];

        // Hanya timpa foto kalau user upload foto baru, supaya foto lama tidak hilang sia-sia.
        if ($request->hasFile('foto_1')) {
            $this->deleteOldPhoto($cmFinding->foto_path);
            $data['foto_path'] = $this->storeFindingPhoto($request, 'foto_1');
        }
        if ($request->hasFile('foto_2')) {
            $this->deleteOldPhoto($cmFinding->foto_path_2);
            $data['foto_path_2'] = $this->storeFindingPhoto($request, 'foto_2');
        }
        if ($request->hasFile('foto_3')) {
            $this->deleteOldPhoto($cmFinding->foto_path_3);
            $data['foto_path_3'] = $this->storeFindingPhoto($request, 'foto_3');
        }

        $cmFinding->update($data);

        return redirect()->route('cm.index')
            ->with('success', 'Temuan visual berhasil diperbarui.');
    }

    public function destroy(CmMeasurement $cm)
    {
        $asset = $cm->asset;
        $cm->delete();
        $asset->refreshStatus();

        return redirect()->route('cm.index')
            ->with('success', 'Data CM berhasil dihapus.');
    }

    public function destroyFinding(CmFinding $cmFinding)
    {
        $this->deleteOldPhoto($cmFinding->foto_path);
        $this->deleteOldPhoto($cmFinding->foto_path_2);
        $this->deleteOldPhoto($cmFinding->foto_path_3);
        $cmFinding->delete();

        return redirect()->route('cm.index')
            ->with('success', 'Temuan visual berhasil dihapus.');
    }

    public function import(Request $request)
{
    $request->validate(['file' => 'required|mimes:xlsx,xls|max:10240']);

    $spreadsheet = IOFactory::load($request->file('file')->getPathname());
    $sheet       = $spreadsheet->getActiveSheet();
    $rows        = $sheet->toArray();

    $imported = 0;
    $skipped  = 0;
    $errors   = [];

    $getNum = function ($val) {
        if ($val === null || $val === '') return null;
        $val = str_replace(',', '.', trim((string) $val));
        return is_numeric($val) ? (float) $val : null;
    };

    foreach ($rows as $i => $row) {
        if ($i === 0) continue;

        $tanggalExcel = trim($row[0] ?? '');
        $tagNo        = trim($row[1] ?? '');

        if (empty($tanggalExcel) || empty($tagNo)) continue;

        $asset = Asset::where('tag_no', $tagNo)->first();
        if (!$asset) {
            $errors[] = "Baris " . ($i + 1) . ": Equipment '$tagNo' tidak ditemukan.";
            $skipped++;
            continue;
        }

        // Parse tanggal — handle berbagai format input dari Excel
        try {
            $tanggal = null;

            // 1. Excel serial number (numeric)
            if (is_numeric($tanggalExcel)) {
                $tanggal = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($tanggalExcel)->format('Y-m-d');
            }
            // 2. String ISO "YYYY-MM-DD" atau "YYYY/MM/DD"
            elseif (preg_match('/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}$/', $tanggalExcel)) {
                $tanggal = Carbon::parse($tanggalExcel)->format('Y-m-d');
            }
            // 3. String "DD/MM/YYYY" atau "DD-MM-YYYY"
            elseif (preg_match('/^\d{1,2}[-\/]\d{1,2}[-\/]\d{4}$/', $tanggalExcel)) {
                $dt = \DateTime::createFromFormat('d/m/Y', $tanggalExcel);
                if (!$dt) $dt = \DateTime::createFromFormat('d-m-Y', $tanggalExcel);
                if (!$dt) $dt = \DateTime::createFromFormat('m/d/Y', $tanggalExcel);
                $tanggal = $dt ? $dt->format('Y-m-d') : null;
            }
            // 4. Fallback: biarkan Carbon mencoba segala format
            else {
                $tanggal = Carbon::parse($tanggalExcel)->format('Y-m-d');
            }

            if ($tanggal === null) throw new \Exception('Gagal parse');
        } catch (\Exception $e) {
            $errors[] = "Baris " . ($i + 1) . ": Format tanggal '$tanggalExcel' tidak valid.";
            $skipped++;
            continue;
        }

        try {
            CmMeasurement::create([
                'tanggal'          => $tanggal,
                'asset_id'         => $asset->id,
                'measured_by'      => null,
                'driver_de_vib_v'  => $getNum($row[3] ?? null),
                'driver_de_vib_h'  => $getNum($row[4] ?? null),
                'driver_de_vib_a'  => $getNum($row[5] ?? null),
                'driver_de_cf'     => $getNum($row[6] ?? null),
                'driver_de_temp'   => $getNum($row[7] ?? null),
                'driver_nde_vib_v' => $getNum($row[8] ?? null),
                'driver_nde_vib_h' => $getNum($row[9] ?? null),
                'driver_nde_vib_a' => $getNum($row[10] ?? null),
                'driver_nde_cf'    => $getNum($row[11] ?? null),
                'driver_nde_temp'  => $getNum($row[12] ?? null),
                'driver_ampere'    => $getNum($row[13] ?? null),
                'driven_de_vib_v'  => $getNum($row[14] ?? null),
                'driven_de_vib_h'  => $getNum($row[15] ?? null),
                'driven_de_vib_a'  => $getNum($row[16] ?? null),
                'driven_de_cf'     => $getNum($row[17] ?? null),
                'driven_de_temp'   => $getNum($row[18] ?? null),
                'driven_nde_vib_v' => $getNum($row[19] ?? null),
                'driven_nde_vib_h' => $getNum($row[20] ?? null),
                'driven_nde_vib_a' => $getNum($row[21] ?? null),
                'driven_nde_cf'    => $getNum($row[22] ?? null),
                'driven_nde_temp'  => $getNum($row[23] ?? null),
                'catatan'          => trim($row[24] ?? ''),
            ]);

            $asset->refreshStatus();
            $imported++;
        } catch (\Exception $e) {
            $errors[] = "Baris " . ($i + 1) . ": Gagal simpan — " . $e->getMessage();
            $skipped++;
        }
    }

    $msg = "Import selesai: {$imported} data pengukuran ditambahkan.";
    if ($skipped > 0) $msg .= " ({$skipped} baris dilewati).";

    return redirect()->back()
        ->with('success', $msg)
        ->with('import_errors', $errors);
}

    public function importForm()
    {
        return view('cm.import');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template_CM');

        $headers = [
            'Tanggal (YYYY-MM-DD)', 'Tag No Equipment', 'NIK Pengukur',
            'Dr DE Vib V', 'Dr DE Vib H', 'Dr DE Vib A', 'Dr DE CF', 'Dr DE Temp',
            'Dr NDE Vib V', 'Dr NDE Vib H', 'Dr NDE Vib A', 'Dr NDE CF', 'Dr NDE Temp',
            'Dr Ampere',
            'Dn DE Vib V', 'Dn DE Vib H', 'Dn DE Vib A', 'Dn DE CF', 'Dn DE Temp',
            'Dn NDE Vib V', 'Dn NDE Vib H', 'Dn NDE Vib A', 'Dn NDE CF', 'Dn NDE Temp',
            'Catatan',
        ];

        $sheet->fromArray($headers, null, 'A1');

        foreach (range('A', 'Y') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Template_Import_CM.xlsx"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    public function importFindingForm()
    {
        return view('cm.import-finding');
    }

    public function downloadTemplateFinding()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template_Finding');

        $headers = [
            'Tanggal (YYYY-MM-DD)', 'Tag No Equipment', 'Kategori', 'Severity (low/medium/high)',
            'Deskripsi', 'Analysis', 'Action', 'PIC (Nama)', 'Date Action (YYYY-MM-DD)', 'Remark',
        ];

        $sheet->fromArray($headers, null, 'A1');

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Template_Import_Finding_CM.xlsx"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    private function parseFindingDate($raw): ?string
    {
        $raw = trim((string) $raw);
        if ($raw === '') return null;

        try {
            if (is_numeric($raw)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($raw)->format('Y-m-d');
            }
            if (preg_match('/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}$/', $raw)) {
                return Carbon::parse($raw)->format('Y-m-d');
            }
            if (preg_match('/^\d{1,2}[-\/]\d{1,2}[-\/]\d{4}$/', $raw)) {
                $dt = \DateTime::createFromFormat('d/m/Y', $raw) ?: \DateTime::createFromFormat('d-m-Y', $raw);
                return $dt ? $dt->format('Y-m-d') : null;
            }
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function importFinding(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls|max:10240']);

        $spreadsheet = IOFactory::load($request->file('file')->getPathname());
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray();

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($rows as $i => $row) {
            if ($i === 0) continue;

            $tanggalRaw = trim($row[0] ?? '');
            $tagNo      = trim($row[1] ?? '');

            if (empty($tanggalRaw) || empty($tagNo)) continue;

            $asset = Asset::where('tag_no', $tagNo)->first();
            if (!$asset) {
                $skipped++;
                continue;
            }

            $tanggal = $this->parseFindingDate($tanggalRaw);
            if (!$tanggal) {
                $errors[] = "Baris " . ($i + 1) . ": Format tanggal '$tanggalRaw' tidak valid.";
                $skipped++;
                continue;
            }

            $kategori = trim($row[2] ?? '');
            if (empty($kategori)) {
                $errors[] = "Baris " . ($i + 1) . ": Kategori kosong.";
                $skipped++;
                continue;
            }

            $severity = strtolower(trim($row[3] ?? ''));
            if (!in_array($severity, ['low', 'medium', 'high'])) {
                $severity = 'low';
            }

            $picName = trim($row[7] ?? '');
            $pic = $picName !== '' ? Employee::whereRaw('LOWER(name) = ?', [strtolower($picName)])->first() : null;

            $dateActionRaw = trim($row[8] ?? '');
            $dateAction = $dateActionRaw !== '' ? $this->parseFindingDate($dateActionRaw) : null;

            try {
                CmFinding::create([
                    'asset_id'    => $asset->id,
                    'tanggal'     => $tanggal,
                    'reported_by' => null,
                    'kategori'    => $kategori,
                    'severity'    => $severity,
                    'deskripsi'   => trim($row[4] ?? '') ?: null,
                    'status'      => 'open',
                    'analysis'    => trim($row[5] ?? '') ?: null,
                    'action'      => trim($row[6] ?? '') ?: null,
                    'pic_id'      => $pic->id ?? null,
                    'date_action' => $dateAction,
                    'remark'      => trim($row[9] ?? '') ?: null,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Baris " . ($i + 1) . ": Gagal simpan — " . $e->getMessage();
                $skipped++;
            }
        }

        $msg = "Import selesai: {$imported} temuan visual ditambahkan.";
        if ($skipped > 0) $msg .= " ({$skipped} baris dilewati).";

        return redirect()->route('cm.index')
            ->with('success', $msg)
            ->with('import_errors', $errors);
    }
}