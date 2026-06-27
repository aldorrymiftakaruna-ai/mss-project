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

<div class="flex items-center justify-between mb-5">
    <div class="flex gap-3">
        <select class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white" onchange="filterStatus(this.value)">
            <option value="">Semua Status</option>
            <option value="aman">Aman</option>
            <option value="kritis">Kritis</option>
            <option value="habis">Habis</option>
        </select>
    </div>
    <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
        class="bg-[#0E9E8E] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#0a7a6d] transition">
        + Tambah Spare Part
    </button>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-3 gap-4 mb-5">
    <div class="bg-white rounded-xl p-4 border border-gray-200">
        <div class="text-xs text-gray-500 mb-1">Total Part</div>
        <div class="text-2xl font-bold text-gray-800">{{ $spareParts->count() }}</div>
    </div>
    <div class="bg-white rounded-xl p-4 border border-amber-200">
        <div class="text-xs text-amber-600 mb-1">Stok Kritis</div>
        <div class="text-2xl font-bold text-amber-500">{{ $spareParts->filter(fn($s) => $s->status === 'kritis')->count() }}</div>
    </div>
    <div class="bg-white rounded-xl p-4 border border-red-200">
        <div class="text-xs text-red-500 mb-1">Stok Habis</div>
        <div class="text-2xl font-bold text-red-500">{{ $spareParts->filter(fn($s) => $s->status === 'habis')->count() }}</div>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200">
    <table class="w-full text-sm">
        <thead class="border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase">
                <th class="px-5 py-3 text-left">Kode Material</th>
                <th class="px-5 py-3 text-left">Deskripsi</th>
                <th class="px-5 py-3 text-left">Kategori</th>
                <th class="px-5 py-3 text-left">Stok Min</th>
                <th class="px-5 py-3 text-left">Stok Tersedia</th>
                <th class="px-5 py-3 text-left">Status</th>
                <th class="px-5 py-3 text-left">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($spareParts as $part)
            <tr class="hover:bg-gray-50 part-row" data-status="{{ $part->status }}">
                <td class="px-5 py-3 font-mono text-xs font-semibold text-gray-700">{{ $part->kode_material }}</td>
                <td class="px-5 py-3 text-gray-800">{{ $part->deskripsi }}</td>
                <td class="px-5 py-3 text-gray-500">{{ $part->kategori ?? '—' }}</td>
                <td class="px-5 py-3 text-gray-600">{{ $part->stok_minimum }} {{ $part->satuan }}</td>
                <td class="px-5 py-3 text-gray-600">{{ $part->stok_tersedia }} {{ $part->satuan }}</td>
                <td class="px-5 py-3">
                    @php
                        $colors = ['aman'=>'bg-green-100 text-green-700','kritis'=>'bg-amber-100 text-amber-700','habis'=>'bg-red-100 text-red-700'];
                    @endphp
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$part->status] }}">
                        {{ ucfirst($part->status) }}
                    </span>
                </td>
                <td class="px-5 py-3">
                    <form action="{{ route('spareparts.destroy', $part) }}" method="POST"
                        onsubmit="return confirm('Hapus spare part ini?')">
                        @csrf @method('DELETE')
                        <button class="text-red-400 hover:text-red-600 text-xs">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-5 py-10 text-center text-gray-400">Belum ada data spare part.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal Tambah --}}
<div id="modal-add" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Tambah Spare Part</h3>
            <button onclick="document.getElementById('modal-add').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form action="{{ route('spareparts.store') }}" method="POST" class="space-y-4">
            @csrf
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
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Kategori</label>
                    <input type="text" name="kategori" placeholder="Bearing, Seal..."
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
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
                <button type="submit" class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">
                    Simpan
                </button>
                <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')"
                    class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function filterStatus(val) {
    document.querySelectorAll('.part-row').forEach(row => {
        row.style.display = (!val || row.dataset.status === val) ? '' : 'none';
    });
}
</script>

@endsection