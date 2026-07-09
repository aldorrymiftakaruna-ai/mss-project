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
            <p class="text-xs text-gray-400 mb-4">Minimal 2 kriteria. Pilih kriteria dari daftar yang tersedia. Setiap kriteria hanya bisa dipilih sekali.</p>

            <div id="criteriaList" class="space-y-2">
                <div class="flex items-center gap-2 criterion-row">
                    <select name="criteria[]" required
                        class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]/30 focus:border-[#0E9E8E] bg-white criterion-select">
                        <option value="">-- Pilih Kriteria --</option>
                        @foreach($validCriteria as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="remove-criterion p-2 text-gray-400 hover:text-red-500 transition" title="Hapus">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex items-center gap-2 criterion-row">
                    <select name="criteria[]" required
                        class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]/30 focus:border-[#0E9E8E] bg-white criterion-select">
                        <option value="">-- Pilih Kriteria --</option>
                        @foreach($validCriteria as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
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

    // Daftar semua value yang valid
    const allValues = @json(array_keys($validCriteria));

    /**
     * Kumpulkan semua nilai yang sudah dipilih di semua dropdown.
     */
    function getSelectedValues() {
        const selects = list.querySelectorAll('.criterion-select');
        return Array.from(selects).map(s => s.value).filter(v => v !== '');
    }

    /**
     * Update opsi setiap dropdown: opsi yang sudah dipilih di row lain jadi disabled.
     */
    function refreshOptions() {
        const selects = list.querySelectorAll('.criterion-select');
        const selected = getSelectedValues();

        selects.forEach(select => {
            const currentVal = select.value;
            const options = select.querySelectorAll('option');

            options.forEach(opt => {
                if (opt.value === '') return;
                if (selected.filter(v => v === opt.value).length > (currentVal === opt.value ? 1 : 0)) {
                    opt.disabled = true;
                } else {
                    opt.disabled = false;
                }
            });
        });
    }

    function updateCount() {
        const rows = list.querySelectorAll('.criterion-row');
        countDisplay.textContent = rows.length + ' kriteria (minimal 2)';
        refreshOptions();
    }

    list.addEventListener('click', function(e) {
        const btn = e.target.closest('.remove-criterion');
        if (!btn) return;
        const row = btn.closest('.criterion-row');
        if (!row) return;
        if (list.querySelectorAll('.criterion-row').length <= 2) return;
        row.remove();
        updateCount();
    });

    list.addEventListener('change', function(e) {
        if (e.target.classList.contains('criterion-select')) {
            refreshOptions();
        }
    });

    addBtn.addEventListener('click', function() {
        const div = document.createElement('div');
        div.className = 'flex items-center gap-2 criterion-row';

        const select = document.createElement('select');
        select.name = 'criteria[]';
        select.required = true;
        select.className = 'flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]/30 focus:border-[#0E9E8E] bg-white criterion-select';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '-- Pilih Kriteria --';
        select.appendChild(placeholder);

        @foreach($validCriteria as $key => $label)
        (function() {
            const opt = document.createElement('option');
            opt.value = '{{ $key }}';
            opt.textContent = '{{ $label }}';
            select.appendChild(opt);
        })();
        @endforeach

        div.appendChild(select);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'remove-criterion p-2 text-gray-400 hover:text-red-500 transition';
        btn.title = 'Hapus';
        btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>';
        div.appendChild(btn);

        list.appendChild(div);
        updateCount();
    });

    updateCount();
})();
</script>
@endpush
