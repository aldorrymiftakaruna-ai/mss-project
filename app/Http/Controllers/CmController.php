<?php

namespace App\Http\Controllers;

use App\Models\CmMeasurement;
use App\Models\CmFinding;
use App\Models\Asset;
use App\Models\Employee;
use Illuminate\Http\Request;

class CmController extends Controller
{
    public function index()
    {
        $measurements = CmMeasurement::with(['asset', 'measuredBy'])->latest()->get();
        $findings = CmFinding::with(['asset', 'reporter'])->latest()->get();
        $assets = Asset::all();
        $employees = Employee::where('is_active', true)->get();
        return view('cm.index', compact('measurements', 'findings', 'assets', 'employees'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_id' => 'required',
            'measured_by' => 'required',
            'tanggal' => 'required|date',
        ]);

        if ($request->type === 'measurement') {
            CmMeasurement::create($request->except('type'));
        } else {
            CmFinding::create($request->except('type'));
        }

        return redirect()->route('cm.index')->with('success', 'Data CM berhasil disimpan.');
    }
}