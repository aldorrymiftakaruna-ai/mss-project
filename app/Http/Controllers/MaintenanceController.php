<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceReport;
use App\Models\ManpowerLog;
use App\Models\Asset;
use App\Models\Employee;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index()
    {
        $reports = MaintenanceReport::with(['asset', 'reporter'])->latest()->get();
        $assets = Asset::all();
        $employees = Employee::where('is_active', true)->get();
        return view('maintenance.index', compact('reports', 'assets', 'employees'));
    }

    public function show(MaintenanceReport $maintenance)
    {
        $maintenance->load(['asset', 'reporter', 'manpowerLogs.employee']);
        return view('maintenance.show', compact('maintenance'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_id' => 'required',
            'reported_by' => 'required',
            'shift' => 'required',
            'tanggal' => 'required|date',
            'jenis' => 'required',
            'deskripsi_masalah' => 'required',
            'downtime_minutes' => 'nullable|integer|min:0',
            'is_overtime' => 'nullable|boolean',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
        ]);

        $report = MaintenanceReport::create($request->all());

        // Buat ManpowerLog otomatis untuk pelapor
        ManpowerLog::create([
            'maintenance_report_id' => $report->id,
            'employee_id'           => $request->reported_by,
            'keterangan'            => 'proses',
        ]);

        return redirect()->route('maintenance.index')->with('success', 'Laporan berhasil ditambahkan.');
    }

    public function destroy(MaintenanceReport $maintenance)
    {
        $maintenance->delete();
        return redirect()->route('maintenance.index')->with('success', 'Laporan berhasil dihapus.');
    }

    /**
     * Tambah teknisi ke manpower log suatu laporan.
     */
    public function addManpower(Request $request, MaintenanceReport $maintenance)
    {
        $request->validate([
            'employee_id'  => 'required|exists:employees,id',
            'durasi_menit' => 'nullable|integer|min:0',
        ]);

        ManpowerLog::create([
            'maintenance_report_id' => $maintenance->id,
            'employee_id'           => $request->employee_id,
            'durasi_menit'          => $request->durasi_menit,
        ]);

        return redirect()->route('maintenance.show', $maintenance)
                         ->with('success', 'Teknisi berhasil ditambahkan ke laporan.');
    }
    /**
     * Update downtime & overtime metrics untuk suatu laporan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\MaintenanceReport  $maintenance
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateMetrics(Request $request, MaintenanceReport $maintenance)
    {
        $request->validate([
            'downtime_minutes' => 'nullable|integer|min:0',
            'is_overtime' => 'nullable|boolean',
            'overtime_hours' => 'nullable|numeric|min:0|max:24',
        ]);

        $maintenance->update([
            'downtime_minutes' => $request->downtime_minutes,
            'is_overtime'      => $request->boolean('is_overtime'),
            'overtime_hours'   => $request->is_overtime ? $request->overtime_hours : null,
        ]);

        return redirect()->route('maintenance.show', $maintenance)
                         ->with('success', 'Data downtime & lembur berhasil diperbarui.');
    }
}
