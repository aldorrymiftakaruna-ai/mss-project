@extends('layouts.app')

@section('title', 'Import BOM')
@section('page-title', 'Import BOM')
@section('page-sub', '— Upload file Excel BOM spare part')

@section('content')

@if(session('error'))
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    {{ session('error') }}
</div>
@endif

<div class="max-w-xl">
    <div class="bg-white rounded-xl border border-gray-200 p-6">

        {{-- Panduan --}}
        <div class="mb-6 p-4 bg-teal-50 border border-teal-100 rounded-lg text-sm text-teal-800">
            <p class="font-semibold mb-2">Format kolom Excel (tidak perlu header):</p>
            <div class="overflow-x-auto">
                <table class="text-xs w-full border border-teal-200 rounded">
                    <thead class="bg-teal-100">
                        <tr>
                            <th class="px-2 py-1 text-left">A — Sub Komponen</th>
                            <th class="px-2 py-1 text-left">B — Tag No</th>
                            <th class="px-2 py-1 text-left">C — Kode Material</th>
                            <th class="px-2 py-1 text-left">D — Deskripsi</th>
                            <th class="px-2 py-1 text-left">E — Qty</th>
                            <th class="px-2 py-1 text-left">F — Satuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-white">
                            <td class="px-2 py-1">XWD5-35</td>
                            <td class="px-2 py-1">SC08</td>
                            <td class="px-2 py-1">6180100071</td>
                            <td class="px-2 py-1">GEAR BOX CYCLO</td>
                            <td class="px-2 py-1">1</td>
                            <td class="px-2 py-1">Pcs</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <ul class="list-disc list-inside space-y-1 text-xs mt-3">
                <li>Kolom A (Sub Komponen) → masuk ke kolom <strong>Keterangan</strong> BOM</li>
                <li>Tag No dan Kode Material harus sudah ada di database</li>
                <li>Baris dengan Tag No atau Kode Material kosong dilewati otomatis</li>
                <li>BOM yang sudah ada (duplikat) dilewati — tidak ditimpa</li>
                <li>Tidak perlu baris header — langsung data dari baris pertama</li>
            </ul>
        </div>

        {{-- Download Template --}}
        <a href="{{ route('assets.bom.template') }}"
           class="flex items-center gap-2 w-full justify-center border border-[#0E9E8E] text-[#0E9E8E] py-2 rounded-lg text-sm hover:bg-teal-50 transition mb-6">
            ↓ Download Template Excel BOM
        </a>

        {{-- Upload Form --}}
        <form action="{{ route('assets.bom.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="text-xs text-gray-500 mb-1 block">File Excel (.xlsx)</label>
                <input type="file" name="file" accept=".xlsx,.xls" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                @error('file')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex gap-3">
                <button type="submit"
                    class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">
                    Upload & Import BOM
                </button>
                <a href="{{ route('assets.index') }}"
                    class="flex-1 text-center border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

@endsection