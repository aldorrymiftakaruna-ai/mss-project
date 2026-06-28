<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Company;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AssetController extends Controller
{
    public function index()
    {
        $assets = Asset::with('company')->latest()->get();
        $companies = Company::all();
        return view('assets.index', compact('assets', 'companies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required',
            'tag_no'     => 'required|unique:assets',
            'description' => 'required',
        ]);

        Asset::create($request->all());
        return redirect()->route('assets.index')->with('success', 'Equipment berhasil ditambahkan.');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return redirect()->route('assets.index')->with('success', 'Equipment berhasil dihapus.');
    }

    public function importForm()
    {
        return view('assets.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        // Baris 0 = header kolom, baris 1 = keterangan/contoh
        // Data mulai dari baris index 2 (baris ke-3 di Excel)
        foreach ($rows as $index => $row) {
            if ($index < 2) continue; // skip header & keterangan

            $tagNo = trim($row[0] ?? '');

            // Skip baris kosong
            if (empty($tagNo)) continue;

            // Cari company berdasarkan company_code (CES atau NPA)
            $companyCode = strtoupper(trim($row[2] ?? ''));
            $company = Company::where('code', $companyCode)->first();

            if (!$company) {
                $errors[] = "Baris " . ($index + 1) . ": PT '{$companyCode}' tidak ditemukan di database.";
                $skipped++;
                continue;
            }

            // Skip kalau tag_no sudah ada (hindari duplikat)
            if (Asset::where('tag_no', $tagNo)->exists()) {
                $skipped++;
                continue;
            }

            // Konversi nilai numerik — jaga-jaga kalau kosong atau teks
            $motorKw      = is_numeric($row[6]) ? (float) $row[6] : null;
            $motorRpm     = is_numeric($row[7]) ? (int) $row[7] : null;
            $motorAmpere  = is_numeric($row[8]) ? (float) $row[8] : null;

            Asset::create([
                'company_id'    => $company->id,
                'tag_no'        => $tagNo,
                'description'   => trim($row[1] ?? ''),
                'model'         => trim($row[3] ?? '') ?: null,
                'serial_number' => trim($row[4] ?? '') ?: null,
                'head_capacity' => trim($row[5] ?? '') ?: null,
                'motor_kw'      => $motorKw,
                'motor_rpm'     => $motorRpm,
                'motor_ampere'  => $motorAmpere,
                'status'        => 'normal',
            ]);

            $imported++;
        }

        $message = "Import selesai: {$imported} equipment berhasil diimport.";
        if ($skipped > 0) {
            $message .= " {$skipped} baris dilewati (duplikat atau PT tidak ditemukan).";
        }

        return redirect()->route('assets.index')
            ->with('success', $message)
            ->with('import_errors', $errors);
    }
}