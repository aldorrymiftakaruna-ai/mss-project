@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto mt-8">
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-bold mb-1">Import Equipment dari Excel</h2>
        <p class="text-sm text-gray-500 mb-6">
            Format kolom Excel: <strong>tag_no, description, company_code, model, serial_number, head_capacity, motor_kw, motor_rpm, motor_ampere</strong><br>
            Data dimulai dari baris ke-3 (baris 1 header, baris 2 keterangan).
        </p>

        @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <p class="text-sm font-semibold text-yellow-800 mb-2">Peringatan:</p>
            <ul class="text-sm text-yellow-700 list-disc list-inside">
                @foreach(session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('assets.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Pilih file Excel (.xlsx atau .xls)
                </label>
                <input type="file" name="file" accept=".xlsx,.xls"
                    class="block w-full text-sm text-gray-500 border border-gray-300 rounded-lg p-2 cursor-pointer">
                @error('file')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex gap-3">
                <button type="submit"
                    class="bg-teal-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-teal-700">
                    Upload & Import
                </button>
                <a href="{{ route('assets.index') }}"
                    class="bg-gray-100 text-gray-700 px-5 py-2 rounded-lg text-sm font-semibold hover:bg-gray-200">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection