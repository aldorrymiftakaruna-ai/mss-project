<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Company;
use Illuminate\Http\Request;

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
            'tag_no' => 'required|unique:assets',
            'name' => 'required',
        ]);

        Asset::create($request->all());
        return redirect()->route('assets.index')->with('success', 'Equipment berhasil ditambahkan.');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();
        return redirect()->route('assets.index')->with('success', 'Equipment berhasil dihapus.');
    }
}