<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CmMeasurement;
use App\Models\Employee;
use Illuminate\Http\Request;

class CmController extends Controller
{
    public function index()
{
    $cms       = CmMeasurement::with('asset.company')->latest('tanggal')->get();
    $findings  = \App\Models\CmFinding::with('asset.company')->latest('tanggal')->get();
    $assets    = Asset::with('company')->orderBy('tag_no')->get();
    $employees = Employee::orderBy('name')->get();
    return view('cm.index', compact('cms', 'findings', 'assets', 'employees'));
}

    public function store(Request $request)
    {
        $request->validate([
            'asset_id'    => 'required|exists:assets,id',
            'measured_by' => 'required|string|max:100',
            'tanggal'     => 'required|date',
        ]);

        $cm = CmMeasurement::create($request->except('_token'));

        // ── Auto-update status equipment ──────────────────────────
        $asset = Asset::find($request->asset_id);
        $asset->refreshStatus();

        return redirect()->route('cm.index')
                         ->with('success', 'Data CM berhasil disimpan. Status equipment diperbarui.');
    }

    public function destroy(CmMeasurement $cm)
    {
        $asset = $cm->asset;
        $cm->delete();

        // Recalculate status setelah hapus data CM
        $asset->refreshStatus();

        return redirect()->route('cm.index')
                         ->with('success', 'Data CM berhasil dihapus.');
    }
}