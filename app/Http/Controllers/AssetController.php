<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Company;
use App\Models\SparePart;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class AssetController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────
    public function index()
    {
        $assets    = Asset::with('company')->latest()->get();
        $companies = Company::all();
        return view('assets.index', compact('assets', 'companies'));
    }

    // ── Detail ────────────────────────────────────────────────────
    public function show(Asset $asset)
    {
        $asset->load([
            'company',
            'cmMeasurements',
            'cmFindings',
            'maintenanceReports',
            'spareParts',
        ]);

        $availableParts = SparePart::where('company_id', $asset->company_id)
                            ->orderBy('kode_material')
                            ->get();

        return view('assets.show', compact('asset', 'availableParts'));
    }

    // ── Create ────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'company_id'  => 'required',
            'tag_no'      => 'required|unique:assets',
            'description' => 'required',
        ]);

        Asset::create($request->only([
            'company_id', 'tag_no', 'description', 'model',
            'serial_number', 'head_capacity', 'motor_kw',
            'motor_rpm', 'motor_ampere',
        ]) + ['status' => 'normal']);

        return redirect()->route('assets.index')
                         ->with('success', 'Equipment berhasil ditambahkan.');
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(Asset $asset)
    {
        abort(404);
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, Asset $asset)
    {
        $request->validate([
            'company_id'    => 'required|exists:companies,id',
            'tag_no'        => 'required|unique:assets,tag_no,' . $asset->id,
            'description'   => 'required|string|max:255',
            'model'         => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'head_capacity' => 'nullable|string|max:100',
            'motor_kw'      => 'nullable|numeric|min:0',
            'motor_rpm'     => 'nullable|integer|min:0',
            'motor_ampere'  => 'nullable|numeric|min:0',
        ]);

        $asset->update($request->only([
            'company_id', 'tag_no', 'description', 'model',
            'serial_number', 'head_capacity', 'motor_kw',
            'motor_rpm', 'motor_ampere',
        ]));

        return redirect()->route('assets.show', $asset)
                         ->with('success', 'Equipment berhasil diperbarui.');
    }

    // ── Delete ────────────────────────────────────────────────────
    public function destroy(Asset $asset)
    {
        $asset->delete();
        return redirect()->route('assets.index')
                         ->with('success', 'Equipment berhasil dihapus.');
    }

    // ── Attach Spare Part ke BOM ──────────────────────────────────
    public function attachSparePart(Request $request, Asset $asset)
    {
        $request->validate([
            'spare_part_id'    => 'required|exists:spare_parts,id',
            'jumlah_kebutuhan' => 'required|integer|min:1',
            'keterangan'       => 'nullable|string|max:255',
        ]);

        if ($asset->spareParts()->where('spare_part_id', $request->spare_part_id)->exists()) {
            $asset->spareParts()->updateExistingPivot($request->spare_part_id, [
                'jumlah_kebutuhan' => $request->jumlah_kebutuhan,
                'keterangan'       => $request->keterangan,
            ]);
            return redirect()->route('assets.show', $asset)
                             ->with('success', 'BOM berhasil diperbarui.');
        }

        $asset->spareParts()->attach($request->spare_part_id, [
            'jumlah_kebutuhan' => $request->jumlah_kebutuhan,
            'keterangan'       => $request->keterangan,
        ]);

        return redirect()->route('assets.show', $asset)
                         ->with('success', 'Spare part berhasil ditambahkan ke BOM.');
    }

    // ── Detach Spare Part dari BOM ────────────────────────────────
    public function detachSparePart(Asset $asset, SparePart $sparePart)
    {
        $asset->spareParts()->detach($sparePart->id);
        return redirect()->route('assets.show', $asset)
                         ->with('success', 'Spare part berhasil dihapus dari BOM.');
    }

    // ── BOM Import Form ───────────────────────────────────────────
    public function bomImportForm()
    {
        return view('assets.bom-import');
    }

    // ── BOM Download Template ─────────────────────────────────────
    public function bomTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('BOM');

        $headers = ['Sub Komponen / Model', 'Tag No', 'Kode Material', 'Deskripsi', 'Qty', 'Satuan'];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0E9E8E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        $examples = [
            ['XWD5-35', 'SC08', '6180100071', 'GEAR BOX CYCLO XWD5-35', 1, 'Pcs'],
            ['XWD5-35', 'SC08', '6180100222', 'Chain RS-80-2', 1, 'Pcs'],
            ['Bearing Screw', 'SC08', '6220100060', 'UCF 210', 2, 'Pcs'],
        ];
        $sheet->fromArray($examples, null, 'A2');
        $sheet->getStyle('A2:F4')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
        ]);

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="MSS_Template_BOM.xlsx"');
        header('Cache-Control: max-age=0');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    // ── BOM Import Process ────────────────────────────────────────
    public function bomImport(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls|max:10240']);

        $spreadsheet = IOFactory::load($request->file('file')->getPathname());
        $rows        = $spreadsheet->getActiveSheet()->toArray();

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($rows as $i => $row) {
            $subKomponen = trim($row[0] ?? '');
            $tagNo       = trim($row[1] ?? '');
            $kode        = trim($row[2] ?? '');

            // Skip baris kosong atau header
            if (empty($tagNo) || empty($kode)) { $skipped++; continue; }
            if (strtolower($tagNo) === 'tag no') continue;

            $qty = is_numeric($row[4] ?? '') ? (int) $row[4] : 1;
            $qty = max(1, $qty);

            $asset = Asset::where('tag_no', $tagNo)->first();
            if (!$asset) {
                $errors[] = "Baris " . ($i + 1) . ": Tag No '{$tagNo}' tidak ditemukan.";
                $skipped++; continue;
            }

            // Cari spare part — prioritas PT yang sama
            $part = SparePart::where('kode_material', $kode)
                        ->where('company_id', $asset->company_id)
                        ->first();
            if (!$part) {
                $part = SparePart::where('kode_material', $kode)->first();
            }
            if (!$part) {
                $errors[] = "Baris " . ($i + 1) . ": Kode '{$kode}' tidak ditemukan.";
                $skipped++; continue;
            }

            // Skip duplikat
            if ($asset->spareParts()->where('spare_part_id', $part->id)->exists()) {
                $skipped++; continue;
            }

            $asset->spareParts()->attach($part->id, [
                'jumlah_kebutuhan' => $qty,
                'keterangan'       => $subKomponen ?: null,
            ]);
            $imported++;
        }

        $msg = "Import BOM selesai: {$imported} relasi berhasil diimport.";
        if ($skipped > 0) $msg .= " {$skipped} baris dilewati.";

        return redirect()->route('assets.index')
                         ->with('success', $msg)
                         ->with('import_errors', $errors);
    }

    // ── Excel Import Equipment ────────────────────────────────────
    public function importForm()
    {
        return view('assets.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240',
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getPathname());
        $rows        = $spreadsheet->getActiveSheet()->toArray();

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($rows as $index => $row) {
            if ($index < 2) continue;

            $tagNo = trim($row[0] ?? '');
            if (empty($tagNo)) continue;

            $companyCode = strtoupper(trim($row[2] ?? ''));
            $company     = Company::where('code', $companyCode)->first();

            if (!$company) {
                $errors[] = "Baris " . ($index + 1) . ": PT '{$companyCode}' tidak ditemukan.";
                $skipped++; continue;
            }

            if (Asset::where('tag_no', $tagNo)->exists()) {
                $skipped++; continue;
            }

            Asset::create([
                'company_id'    => $company->id,
                'tag_no'        => $tagNo,
                'description'   => trim($row[1] ?? ''),
                'model'         => trim($row[3] ?? '') ?: null,
                'serial_number' => trim($row[4] ?? '') ?: null,
                'head_capacity' => trim($row[5] ?? '') ?: null,
                'motor_kw'      => is_numeric($row[6]) ? (float) $row[6] : null,
                'motor_rpm'     => is_numeric($row[7]) ? (int)   $row[7] : null,
                'motor_ampere'  => is_numeric($row[8]) ? (float) $row[8] : null,
                'status'        => 'normal',
            ]);
            $imported++;
        }

        $msg = "Import selesai: {$imported} equipment berhasil diimport.";
        if ($skipped) $msg .= " {$skipped} baris dilewati.";

        return redirect()->route('assets.index')
                         ->with('success', $msg)
                         ->with('import_errors', $errors);
    }
}