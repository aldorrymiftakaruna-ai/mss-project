<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceReport;
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

    public function store(Request $request)
    {
        $request->validate([
            'asset_id' => 'required',
            'reported_by' => 'required',
            'shift' => 'required',
            'tanggal' => 'required|date',
            'jenis' => 'required',
            'deskripsi_masalah' => 'required',
        ]);

        MaintenanceReport::create($request->all());
        return redirect()->route('maintenance.index')->with('success', 'Laporan berhasil ditambahkan.');
    }

    public function destroy(MaintenanceReport $maintenance)
    {
        $maintenance->delete();
        return redirect()->route('maintenance.index')->with('success', 'Laporan berhasil dihapus.');
    }
}