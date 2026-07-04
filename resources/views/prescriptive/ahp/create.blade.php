@extends('layouts.app')

@section('title', 'Buat Sesi AHP')
@section('page-title', 'Buat Sesi AHP')
@section('page-sub', '— Tambah kriteria keputusan')

@section('content')
<div class="max-w-xl">
    <form method="POST" action="{{ route('ahp.store') }}" id="ahpForm">
        @csrf

        <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
            <h3 class="font-semibold text-gray-800 mb-4">Informasi Sesi</h3>

            <div class="mb-4">
                <label for="name" class="block text-xs text-gray-500 mb-1.5">Nama Sesi <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" required
                    class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]/30 focus:border-[#0E9E8E]"
                    placeholder="Contoh: Prioritas Equipment 2026" value="{{ old('name') }}">
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5 mb-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">Kriteria Keputusan</h3>
                <button type="button" id="addCriterion" class="text-xs px-3 py-1.5 rounded-lg bg-[#0E9E8E]/10 text-[#0E9E8E] hover:bg-[#0E9E8E]/20 transition font-medium">
                    + Tambah Kriteria
                </button>
            </div>
            <p class="text-xs text-gray-400 mb-4">Minimal 2 kriteria. Nama kriteria akan digunakan sebagai label di matriks pairwise.</p>

            <div id="criteriaList" class="space-y-2">
                <div class="flex items-center gap-2 criterion-row">
                    <input type="text" name="criteria[]" required
                        class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]/30 focus:border-[#0E9E8E]"
                        placeholder="Nama kriteria (contoh: Biaya)">
                    <button type="button" class="remove-criterion p-2 text-gray-400 hover:text-red-500 transition" title="Hapus">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex items-center gap-2 criterion-row">
                    <input type="text" name="criteria[]" required
                        class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]/30 focus:border-[#0E9E8E]"
                        placeholder="Nama kriteria (contoh: Kualitas)">
                    <button type="button" class="remove-criterion p-2 text-gray-400 hover:text-red-500 transition" title="Hapus">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <p id="criteriaCount" class="text-xs text-gray-400 mt-3">2 kriteria (minimal 2)</p>
            @error('criteria') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            @error('criteria.*') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="px-5 py-2.5 bg-[#0E9E8E] text-white rounded-lg hover:bg-[#0B8A7C] transition text-sm font-medium">
                Buat Sesi & Lanjut ke Pairwise
            </button>
            <a href="{{ route('ahp.index') }}" class="px-5 py-2.5 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition text-sm">Batal</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function() {
    const list = document.getElementById('criteriaList');
    const addBtn = document.getElementById('addCriterion');
    const countDisplay = document.getElementById('criteriaCount');

    function updateCount() {
        const rows = list.querySelectorAll('.criterion-row');
        countDisplay.textContent = rows.length + ' kriteria (minimal 2)';
    }

    function createRow(value) {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 criterion-row';
        div.innerHTML = `
            <input type="text" name="criteria[]" required
                class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]/30 focus:border-[#0E9E8E]"
                placeholder="Nama kriteria" value="${value}">
            <button type="button" class="remove-criterion p-2 text-gray-400 hover:text-red-500 transition" title="Hapus">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>`;
        div.querySelector('.remove-criterion').addEventListener('click', function() {
            if (list.querySelectorAll('.criterion-row').length > 2) {
                div.remove();
                updateCount();
            }
        });
        return div;
    }

    addBtn.addEventListener('click', function() {
        list.appendChild(createRow(''));
        updateCount();
    });

    // Hapus handler untuk existing rows
    list.querySelectorAll('.remove-criterion').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const rows = list.querySelectorAll('.criterion-row');
            if (rows.length > 2) {
                this.closest('.criterion-row').remove();
                updateCount();
            }
        });
    });

    updateCount();
})();
</script>
@endpush
