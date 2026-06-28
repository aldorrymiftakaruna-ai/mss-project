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
        $assets    = Asset::with('company')->latest()->get();
        $companies = Company::all();
        return view('assets.index', compact('assets', 'companies'));
    }

    // ── Detail page ───────────────────────────────────────────────
    public function show(Asset $asset)
    {
        $asset->load([
            'company',
            'cmMeasurements',          // sudah di-order latest by tanggal di model
            'cmFindings',
            'maintenanceReports',
            'spareParts',
        ]);

        return view('assets.show', compact('asset'));
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

    // ── Edit / Update ─────────────────────────────────────────────
    public function edit(Asset $asset)
    {
        // Tidak dipakai — edit dilakukan via modal di show page
        abort(404);
    }

    public function update(Request $request, Asset $asset)
    {
        $request->validate([
            'company_id'  => 'required|exists:companies,id',
            'tag_no'      => 'required|unique:assets,tag_no,' . $asset->id,
            'description' => 'required|string|max:255',
            'model'       => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'head_capacity' => 'nullable|string|max:100',
            'motor_kw'    => 'nullable|numeric|min:0',
            'motor_rpm'   => 'nullable|integer|min:0',
            'motor_ampere'=> 'nullable|numeric|min:0',
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

    // ── Excel Import ──────────────────────────────────────────────
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
                $skipped++;
                continue;
            }

            if (Asset::where('tag_no', $tagNo)->exists()) {
                $skipped++;
                continue;
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