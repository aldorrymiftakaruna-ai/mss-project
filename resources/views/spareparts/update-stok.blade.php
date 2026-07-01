@extends('layouts.app')

@section('title', 'Update Stok Spare Part')
@section('page-title', 'Update Stok')
@section('page-sub', '— Import stok aktual dari gudang')

@section('content')
<div class="max-w-lg mx-auto mt-4">
    <div class="bg-white rounded-xl border border-gray-200 p-6">

        <div class="mb-5">
            <h2 class="font-semibold text-gray-800 mb-1">Upload File Stok Gudang</h2>
            <p class="text-sm text-gray-500">
                Format: file Excel dari gudang (kolom A = Kode Material, kolom B = Sisa Stok).
                Data dibaca mulai baris ke-9.
            </p>
        </div>

        @if(session('success'))
        <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
        @endif

        <form action="{{ route('spareparts.updateStok') }}" method="POST"
              enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="text-xs text-gray-500 mb-1 block">PT *</label>
                <select name="company_id" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">Pilih PT</option>
                    @foreach($companies as $company)
                    <option value="{{ $company->id }}">
                        {{ $company->code }} — {{ $company->name }}
                    </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">
                    Pilih PT sesuai file yang diupload (CES atau NPA)
                </p>
            </div>

            <div>
                <label class="text-xs text-gray-500 mb-1 block">File Excel *</label>
                <input type="file" name="file" accept=".xlsx,.xls" required
                    class="block w-full text-sm text-gray-500 border border-gray-200
                           rounded-lg p-2 cursor-pointer">
                @error('file')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm
                           hover:bg-[#0a7a6d] transition font-medium">
                    Update Stok
                </button>
                <a href="{{ route('spareparts.index') }}"
                    class="flex-1 text-center border border-gray-200 text-gray-600 py-2
                           rounded-lg text-sm hover:bg-gray-50 transition">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection