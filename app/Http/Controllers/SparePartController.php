<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Company;
use App\Models\SparePart;
use App\Models\SparePartImage;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SparePartController extends Controller
{
    public function index()
    {
        $spareParts = SparePart::with('company')->latest()->get();
        $companies  = Company::all();
        return view('spareparts.index', compact('spareParts', 'companies'));
    }

    // ── Detail ────────────────────────────────────────────────────
    public function show(SparePart $sparepart)
    {
        $sparepart->load(['company', 'images', 'assets.company']);
        return view('spareparts.show', compact('sparepart'));
    }

    // ── Create ────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'company_id'    => 'required|exists:companies,id',
            'kode_material' => 'required',
            'deskripsi'     => 'required',
        ]);

        SparePart::create($request->only([
            'company_id', 'kode_material', 'deskripsi',
            'satuan', 'stok_minimum', 'stok_tersedia',
        ]));

        return redirect()->route('spareparts.index')
                         ->with('success', 'Spare part berhasil ditambahkan.');
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(Request $request, SparePart $sparepart)
    {
        $request->validate([
            'company_id'    => 'required|exists:companies,id',
            'kode_material' => 'required|unique:spare_parts,kode_material,' . $sparepart->id . ',id,company_id,' . $request->company_id,
            'deskripsi'     => 'required|string|max:255',
            'satuan'        => 'nullable|string|max:50',
            'stok_minimum'  => 'nullable|integer|min:0',
            'stok_tersedia' => 'nullable|integer|min:0',
        ]);

        $sparepart->update($request->only([
            'company_id', 'kode_material', 'deskripsi',
            'satuan', 'stok_minimum', 'stok_tersedia',
        ]));

        return redirect()->route('spareparts.show', $sparepart)
                         ->with('success', 'Spare part berhasil diperbarui.');
    }

    // ── Delete ────────────────────────────────────────────────────
    public function destroy(SparePart $sparepart)
    {
        // Hapus semua gambar dari storage
        foreach ($sparepart->images as $img) {
            \Storage::disk('public')->delete($img->path);
        }
        $sparepart->delete();

        return redirect()->route('spareparts.index')
                         ->with('success', 'Spare part berhasil dihapus.');
    }

    // ── Upload Gambar ─────────────────────────────────────────────
    public function uploadImage(Request $request, SparePart $sparepart)
    {
        $request->validate([
            'images'   => 'required|array|max:10',
            'images.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB per file
            'label'    => 'nullable|string|max:100',
        ]);

        foreach ($request->file('images') as $file) {
            $path = $file->store('spareparts/' . $sparepart->id, 'public');
            SparePartImage::create([
                'spare_part_id' => $sparepart->id,
                'path'          => $path,
                'label'         => $request->label,
            ]);
        }

        return redirect()->route('spareparts.show', $sparepart)
                         ->with('success', 'Gambar berhasil diupload.');
    }

    // ── Hapus Gambar ──────────────────────────────────────────────
    public function deleteImage(SparePart $sparepart, SparePartImage $image)
    {
        \Storage::disk('public')->delete($image->path);
        $image->delete();

        return redirect()->route('spareparts.show', $sparepart)
                         ->with('success', 'Gambar berhasil dihapus.');
    }

    // ── Download Template Excel ───────────────────────────────────
    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0E9E8E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('SparePart');
        $sheet1->fromArray(['kode_material', 'deskripsi', 'satuan', 'stok_minimum', 'stok_tersedia', 'company_code'], null, 'A1');
        $sheet1->fromArray(['BRG-001', 'Bearing SKF 6205 2RS', 'pcs', 2, 5, 'CES'], null, 'A2');
        $sheet1->fromArray(['SEAL-002', 'Mechanical Seal 40mm', 'pcs', 1, 3, 'NPA'], null, 'A3');
        $sheet1->getStyle('A1:F1')->applyFromArray($headerStyle);
        $sheet1->getStyle('A2:F3')->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']]]);
        foreach (range('A', 'F') as $col) $sheet1->getColumnDimension($col)->setAutoSize(true);

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('BOM');
        $sheet2->fromArray(['tag_no', 'kode_material', 'jumlah_kebutuhan', 'keterangan'], null, 'A1');
        $sheet2->fromArray(['SC03A', 'BRG-001', 2, 'Bearing DE Motor'], null, 'A2');
        $sheet2->fromArray(['SC03A', 'SEAL-002', 1, 'Mechanical Seal Pompa'], null, 'A3');
        $sheet2->getStyle('A1:D1')->applyFromArray($headerStyle);
        $sheet2->getStyle('A2:D3')->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']]]);
        foreach (range('A', 'D') as $col) $sheet2->getColumnDimension($col)->setAutoSize(true);

        $spreadsheet->setActiveSheetIndex(0);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="MSS_Template_SparePart_BOM.xlsx"');
        header('Cache-Control: max-age=0');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }

    // ── Import Form ───────────────────────────────────────────────
    public function importForm()
    {
        return view('spareparts.import');
    }

    // ── Import Process ────────────────────────────────────────────
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls|max:10240']);

        $spreadsheet   = IOFactory::load($request->file('file')->getPathname());
        $importedParts = 0; $skippedParts = 0;
        $importedBom   = 0; $skippedBom   = 0;
        $errors        = [];

        $sheet1 = $spreadsheet->getSheetByName('SparePart');
        if (!$sheet1) return back()->with('error', 'Sheet "SparePart" tidak ditemukan.');

        foreach ($sheet1->toArray() as $i => $row) {
            if ($i === 0) continue;
            $kode = trim($row[0] ?? '');
            $companyCode = strtoupper(trim($row[5] ?? ''));
            if (empty($kode)) continue;

            $company = Company::where('code', $companyCode)->first();
            if (!$company) {
                $errors[] = "SparePart baris " . ($i + 1) . ": PT '{$companyCode}' tidak ditemukan.";
                $skippedParts++; continue;
            }

            if (SparePart::where('kode_material', $kode)->where('company_id', $company->id)->exists()) {
                $skippedParts++; continue;
            }

            SparePart::create([
                'company_id'    => $company->id,
                'kode_material' => $kode,
                'deskripsi'     => trim($row[1] ?? ''),
                'satuan'        => trim($row[2] ?? '') ?: null,
                'stok_minimum'  => is_numeric($row[3]) ? (int) $row[3] : 0,
                'stok_tersedia' => is_numeric($row[4]) ? (int) $row[4] : 0,
            ]);
            $importedParts++;
        }

        $sheet2 = $spreadsheet->getSheetByName('BOM');
        if ($sheet2) {
            foreach ($sheet2->toArray() as $i => $row) {
                if ($i === 0) continue;
                $tagNo = trim($row[0] ?? '');
                $kode  = trim($row[1] ?? '');
                if (empty($tagNo) || empty($kode)) continue;

                $asset = Asset::where('tag_no', $tagNo)->first();
                if (!$asset) { $errors[] = "BOM baris " . ($i + 1) . ": Tag No '{$tagNo}' tidak ditemukan."; $skippedBom++; continue; }

                $part = SparePart::where('kode_material', $kode)->first();
                if (!$part) { $errors[] = "BOM baris " . ($i + 1) . ": Kode '{$kode}' tidak ditemukan."; $skippedBom++; continue; }

                if ($asset->spareParts()->where('spare_part_id', $part->id)->exists()) { $skippedBom++; continue; }

                $asset->spareParts()->attach($part->id, [
                    'jumlah_kebutuhan' => is_numeric($row[2]) ? (int) $row[2] : 1,
                    'keterangan'       => trim($row[3] ?? '') ?: null,
                ]);
                $importedBom++;
            }
        }

        $msg = "Import selesai: {$importedParts} spare part dan {$importedBom} BOM berhasil diimport.";
        if ($skippedParts + $skippedBom > 0) $msg .= " ({$skippedParts} spare part, {$skippedBom} BOM dilewati).";

        return redirect()->route('spareparts.index')->with('success', $msg)->with('import_errors', $errors);
    }

    // ── Update Stok Form ──────────────────────────────────────────
    public function updateStokForm()
    {
        $companies = \App\Models\Company::all();
        return view('spareparts.update-stok', compact('companies'));
    }

    // ── Update Stok Process ───────────────────────────────────────
    public function updateStok(Request $request)
    {
        $request->validate([
            'file'       => 'required|mimes:xlsx,xls|max:10240',
            'company_id' => 'required|exists:companies,id',
        ]);

        $rows    = IOFactory::load($request->file('file')->getPathname())
                            ->getActiveSheet()
                            ->toArray();

        $updated = 0;
        $skipped = 0;

        foreach ($rows as $i => $row) {
            // Skip 8 baris pertama (info gudang + header)
            if ($i < 8) continue;

            $kode = trim($row[0] ?? '');
            $stok = $row[1] ?? null;

            // Skip baris kosong atau stok bukan angka
            if (empty($kode) || !is_numeric($stok)) continue;

            $part = SparePart::where('kode_material', $kode)
                             ->where('company_id', $request->company_id)
                             ->first();

            if (!$part) {
                $skipped++;
                continue;
            }

            $part->update(['stok_tersedia' => (int) $stok]);
            $updated++;
        }

        $msg = "{$updated} stok berhasil diperbarui.";
        if ($skipped) $msg .= " {$skipped} kode tidak ditemukan di database.";

        return redirect()->route('spareparts.index')
                         ->with('success', $msg);
    }
}