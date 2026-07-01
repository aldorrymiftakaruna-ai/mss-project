@extends('layouts.app')

@section('title', 'Spare Part')
@section('page-title', 'Spare Part')
@section('page-sub', '— Monitoring stok & reorder')

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
    {{ session('success') }}
</div>
@endif

@if(session('import_errors') && count(session('import_errors')))
<div class="mb-4 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-sm">
    <p class="font-semibold mb-1">Beberapa baris dilewati:</p>
    <ul class="list-disc list-inside space-y-0.5 text-xs">
        @foreach(session('import_errors') as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- Header & Actions --}}
<div class="flex items-center justify-between mb-5">
    <div class="flex gap-3">
        <input type="text" id="search-sp" placeholder="Cari kode / deskripsi..."
            oninput="filterSearch(this.value)"
            class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white w-48">
        <select class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white"
                onchange="filterPT(this.value)">
            <option value="">Semua PT</option>
            @foreach($companies as $company)
            <option value="{{ $company->code }}">{{ $company->code }}</option>
            @endforeach
        </select>
        <select class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white"
                onchange="filterStatus(this.value)">
            <option value="">Semua Status</option>
            <option value="aman">Aman</option>
            <option value="kritis">Kritis</option>
            <option value="habis">Habis</option>
        </select>
    </div>
    <div class="flex gap-3">
        
        {{-- Tombol BOM (Langsung ke halaman Import) --}}
        <a href="{{ route('spareparts.import.form') }}"
            class="border border-gray-200 text-gray-600 text-sm px-4 py-2 rounded-lg hover:bg-gray-50 transition">
            BOM
        </a>

        <a href="{{ route('spareparts.updateStok.form') }}"
            class="border border-gray-200 text-gray-600 text-sm px-4 py-2 rounded-lg hover:bg-gray-50 transition">
            Update Stok
        </a>
        <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
            class="bg-[#0E9E8E] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#0a7a6d] transition">
            + Tambah Spare Part
        </button>
    </div>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-xl p-4 border border-gray-200">
        <div class="text-xs text-gray-500 mb-1">Total Part</div>
        <div class="text-2xl font-bold text-gray-800">{{ $spareParts->count() }}</div>
    </div>
    <div class="bg-white rounded-xl p-4 border border-amber-200">
        <div class="text-xs text-amber-600 mb-1">Stok Kritis</div>
        <div class="text-2xl font-bold text-amber-500">
            {{ $spareParts->filter(fn($s) => $s->status === 'kritis')->count() }}
        </div>
    </div>
    <div class="bg-white rounded-xl p-4 border border-red-200">
        <div class="text-xs text-red-500 mb-1">Stok Habis</div>
        <div class="text-2xl font-bold text-red-500">
            {{ $spareParts->filter(fn($s) => $s->status === 'habis')->count() }}
        </div>
    </div>
</div>

{{-- Tabel --}}
<div class="bg-white rounded-xl border border-gray-200">
    <table class="w-full text-sm">
        <thead class="border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase">
                <th class="px-5 py-3 text-left">Kode Material</th>
                <th class="px-5 py-3 text-left">PT</th>
                <th class="px-5 py-3 text-left">Deskripsi</th>
                <th class="px-5 py-3 text-left">Satuan</th>
                <th class="px-5 py-3 text-center">Stok Min</th>
                                <th class="px-5 py-3 text-center">Stok Tersedia</th>
                <th class="px-5 py-3 text-left">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($spareParts as $part)
            @php
                $colors = [
                    'aman'   => 'bg-green-100 text-green-700',
                    'kritis' => 'bg-amber-100 text-amber-700',
                    'habis'  => 'bg-red-100 text-red-700',
                ];
            @endphp
            <tr class="hover:bg-gray-50 part-row"
                data-status="{{ $part->status }}"
                data-pt="{{ $part->company->code ?? '' }}">
                <td class="px-5 py-3 font-mono text-xs font-semibold">
    <a href="{{ route('spareparts.show', $part) }}"
       class="text-[#0E9E8E] hover:underline">
        {{ $part->kode_material }}
    </a>
</td>
                <td class="px-5 py-3 text-gray-500">{{ $part->company->code ?? '—' }}</td>
                <td class="px-5 py-3 text-gray-800">{{ $part->deskripsi }}</td>
                <td class="px-5 py-3 text-gray-500">{{ $part->satuan ?? '—' }}</td>
                <td class="px-5 py-3 text-center text-gray-600">{{ $part->stok_minimum }}</td>
                <td class="px-5 py-3 text-center text-gray-600">{{ $part->stok_tersedia }}</td>
                <td class="px-5 py-3">
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$part->status] }}">
                        {{ ucfirst($part->status) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-5 py-10 text-center text-gray-400">
                    Belum ada data spare part.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ── Modal Tambah ─────────────────────────────────────────── --}}
<div id="modal-add" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Tambah Spare Part</h3>
            <button onclick="document.getElementById('modal-add').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form action="{{ route('spareparts.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs text-gray-500 mb-1 block">PT *</label>
                <select name="company_id" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">Pilih PT</option>
                    @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->code }} — {{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Kode Material *</label>
                    <input type="text" name="kode_material" required placeholder="6180100076"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Satuan</label>
                    <input type="text" name="satuan" value="Pcs"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Deskripsi *</label>
                <input type="text" name="deskripsi" required placeholder="UCFC 212"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Stok Minimum</label>
                    <input type="number" name="stok_minimum" value="1" min="0"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Stok Tersedia</label>
                    <input type="number" name="stok_tersedia" value="0" min="0"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">
                    Simpan
                </button>
                <button type="button"
                    onclick="document.getElementById('modal-add').classList.add('hidden')"
                    class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── Modal Edit ───────────────────────────────────────────── --}}
<div id="modal-edit" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Edit Spare Part</h3>
            <button onclick="document.getElementById('modal-edit').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form id="form-edit" action="" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="text-xs text-gray-500 mb-1 block">PT *</label>
                <select name="company_id" id="edit-company-id" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->code }} — {{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Kode Material *</label>
                    <input type="text" name="kode_material" id="edit-kode" required
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Satuan</label>
                    <input type="text" name="satuan" id="edit-satuan"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Deskripsi *</label>
                <input type="text" name="deskripsi" id="edit-deskripsi" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Stok Minimum</label>
                    <input type="number" name="stok_minimum" id="edit-stok-min" min="0"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Stok Tersedia</label>
                    <input type="number" name="stok_tersedia" id="edit-stok-tersedia" min="0"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">
                    Simpan Perubahan
                </button>
                <button type="button"
                    onclick="document.getElementById('modal-edit').classList.add('hidden')"
                    class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(part) {
    // Set action URL dengan ID spare part
    document.getElementById('form-edit').action = '/spareparts/' + part.id;

    // Isi field
    document.getElementById('edit-company-id').value    = part.company_id;
    document.getElementById('edit-kode').value          = part.kode_material;
    document.getElementById('edit-satuan').value        = part.satuan ?? '';
    document.getElementById('edit-deskripsi').value     = part.deskripsi;
    document.getElementById('edit-stok-min').value      = part.stok_minimum;
    document.getElementById('edit-stok-tersedia').value = part.stok_tersedia;

    document.getElementById('modal-edit').classList.remove('hidden');
}

function filterPT(val) {
    document.querySelectorAll('.part-row').forEach(row => {
        row.style.display = (!val || row.dataset.pt === val) ? '' : 'none';
    });
}
function filterStatus(val) {
    document.querySelectorAll('.part-row').forEach(row => {
        row.style.display = (!val || row.dataset.status === val) ? '' : 'none';
    });
}
function filterSearch(val) {
    val = val.toLowerCase();
    document.querySelectorAll('.part-row').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
    });
}
</script>

@endsection