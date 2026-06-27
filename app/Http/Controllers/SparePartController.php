<?php

namespace App\Http\Controllers;

use App\Models\SparePart;
use Illuminate\Http\Request;

class SparePartController extends Controller
{
    public function index()
    {
        $spareParts = SparePart::latest()->get();
        return view('spareparts.index', compact('spareParts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_material' => 'required|unique:spare_parts',
            'deskripsi' => 'required',
        ]);

        SparePart::create($request->all());
        return redirect()->route('spareparts.index')->with('success', 'Spare part berhasil ditambahkan.');
    }

    public function destroy(SparePart $sparepart)
    {
        $sparepart->delete();
        return redirect()->route('spareparts.index')->with('success', 'Spare part berhasil dihapus.');
    }
}