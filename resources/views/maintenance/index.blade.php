@extends('layouts.app')

@section('title', 'Laporan Maintenance')
@section('page-title', 'Laporan Maintenance')
@section('page-sub', '— Riwayat corrective & inspeksi')

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
    {{ session('success') }}
</div>
@endif

<div class="flex items-center justify-between mb-5">
    <div class="flex gap-3">
        <select class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white" onchange="filterShift(this.value)">
            <option value="">Semua Shift</option>
            <option value="1">Shift 1</option>
            <option value="2">Shift 2</option>
            <option value="3">Shift 3</option>
        </select>
        <select class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white" onchange="filterStatus(this.value)">
            <option value="">Semua Status</option>
            <option value="open">Open</option>
            <option value="on_progress">On Progress</option>
            <option value="done">Done</option>
        </select>
    </div>
    <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
        class="bg-[#0E9E8E] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#0a7a6d] transition">
        + Tambah Laporan
    </button>
</div>

<div class="bg-white rounded-xl border border-gray-200">
    <table class="w-full text-sm">
        <thead class="border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase">
                <th class="px-5 py-3 text-left">Tanggal</th>
                <th class="px-5 py-3 text-left">Equipment</th>
                <th class="px-5 py-3 text-left">Shift</th>
                <th class="px-5 py-3 text-left">Jenis</th>
                <th class="px-5 py-3 text-left">Dilaporkan Oleh</th>
                <th class="px-5 py-3 text-left">Status</th>
                <th class="px-5 py-3 text-left">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($reports as $report)
            <tr class="hover:bg-gray-50 report-row" data-shift="{{ $report->shift }}" data-status="{{ $report->status }}">
                <td class="px-5 py-3 text-gray-600">{{ $report->tanggal->format('d M Y') }}</td>
                <td class="px-5 py-3">
                    <div class="font-medium text-gray-800">{{ $report->asset->name }}</div>
                    <div class="text-xs text-gray-400 font-mono">{{ $report->asset->tag_no }}</div>
                </td>
                <td class="px-5 py-3 text-gray-500">Shift {{ $report->shift }}</td>
                <td class="px-5 py-3 capitalize text-gray-600">{{ $report->jenis }}</td>
                <td class="px-5 py-3 text-gray-600">{{ $report->reporter->name ?? '—' }}</td>
                <td class="px-5 py-3">
                    @php
                        $colors = ['open'=>'bg-red-100 text-red-700','on_progress'=>'bg-amber-100 text-amber-700','done'=>'bg-green-100 text-green-700'];
                    @endphp
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$report->status] }}">
                        {{ ucfirst(str_replace('_', ' ', $report->status)) }}
                    </span>
                </td>
                <td class="px-5 py-3">
                    <form action="{{ route('maintenance.destroy', $report) }}" method="POST"
                        onsubmit="return confirm('Hapus laporan ini?')">
                        @csrf @method('DELETE')
                        <button class="text-red-400 hover:text-red-600 text-xs">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-5 py-10 text-center text-gray-400">Belum ada laporan maintenance.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal Tambah --}}
<div id="modal-add" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Tambah Laporan Maintenance</h3>
            <button onclick="document.getElementById('modal-add').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form action="{{ route('maintenance.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Equipment *</label>
                    <select name="asset_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">Pilih Equipment</option>
                        @foreach($assets as $asset)
                        <option value="{{ $asset->id }}">{{ $asset->tag_no }} — {{ $asset->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Dilaporkan Oleh *</label>
                    <select name="reported_by" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">Pilih Teknisi</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Tanggal *</label>
                    <input type="date" name="tanggal" required value="{{ date('Y-m-d') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Shift *</label>
                    <select name="shift" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="1">Shift 1 (08-16)</option>
                        <option value="2">Shift 2 (16-24)</option>
                        <option value="3">Shift 3 (00-08)</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Jenis *</label>
                    <select name="jenis" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="corrective">Corrective</option>
                        <option value="breakdown">Breakdown</option>
                        <option value="inspeksi">Inspeksi</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Status</label>
                    <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="open">Open</option>
                        <option value="on_progress">On Progress</option>
                        <option value="done">Done</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Deskripsi Masalah *</label>
                <textarea name="deskripsi_masalah" required rows="2"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Tindakan</label>
                <textarea name="tindakan" rows="2"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></textarea>
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
function filterShift(val) {
    document.querySelectorAll('.report-row').forEach(row => {
        row.style.display = (!val || row.dataset.shift === val) ? '' : 'none';
    });
}
function filterStatus(val) {
    document.querySelectorAll('.report-row').forEach(row => {
        row.style.display = (!val || row.dataset.status === val) ? '' : 'none';
    });
}
</script>

@endsection