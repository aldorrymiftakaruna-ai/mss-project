@extends('layouts.app')

@section('title', 'Import Spare Part')
@section('page-title', 'Import Spare Part')
@section('page-sub', '— Upload file Excel')

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
            <p class="font-semibold mb-2">Panduan import:</p>
            <ol class="list-decimal list-inside space-y-1 text-xs">
                <li>Download template Excel di bawah</li>
                <li>Isi Sheet <strong>SparePart</strong> — data master spare part</li>
                <li>Isi Sheet <strong>BOM</strong> — relasi spare part ke equipment (gunakan tag_no & kode_material yang benar)</li>
                <li>Baris contoh (abu-abu) boleh dihapus</li>
                <li>Upload file yang sudah diisi</li>
            </ol>
        </div>

        {{-- Download Template --}}
        <a href="{{ route('spareparts.template') }}"
           class="flex items-center gap-2 w-full justify-center border border-[#0E9E8E] text-[#0E9E8E] py-2 rounded-lg text-sm hover:bg-teal-50 transition mb-6">
            ↓ Download Template Excel
        </a>

        {{-- Upload Form --}}
        <form action="{{ route('spareparts.import') }}" method="POST" enctype="multipart/form-data">
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
                    Upload & Import
                </button>
                <a href="{{ route('spareparts.index') }}"
                    class="flex-1 text-center border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

@endsection