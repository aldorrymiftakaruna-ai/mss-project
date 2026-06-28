@extends('layouts.app')

@section('title', 'Equipment')
@section('page-title', 'Equipment')
@section('page-sub', '— Status & condition monitoring')

@section('content')

{{-- Alert --}}
@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
    {{ session('success') }}
</div>
@endif

{{-- Header & Add Button --}}
<div class="flex items-center justify-between mb-5">
    <div class="flex gap-3">
        <select class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white" onchange="filterPT(this.value)">
            <option value="">Semua PT</option>
            @foreach($companies as $company)
            <option value="{{ $company->code }}">{{ $company->code }}</option>
            @endforeach
        </select>
        <select class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white" onchange="filterStatus(this.value)">
            <option value="">Semua Status</option>
            <option value="normal">Normal</option>
            <option value="warning">Warning</option>
            <option value="critical">Critical</option>
            <option value="breakdown">Breakdown</option>
        </select>
    </div>
    <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
        class="bg-[#0E9E8E] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#0a7a6d] transition">
        + Tambah Equipment
    </button>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl border border-gray-200">
    <table class="w-full text-sm">
        <thead class="border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase">
                <th class="px-5 py-3 text-left">Tag No</th>
                <th class="px-5 py-3 text-left">Nama Equipment</th>
                <th class="px-5 py-3 text-left">Model</th>
                <th class="px-5 py-3 text-left">PT</th>
                <th class="px-5 py-3 text-left">Tipe</th>
                <th class="px-5 py-3 text-left">Status</th>
                <th class="px-5 py-3 text-left">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($assets as $asset)
            <tr class="hover:bg-gray-50 asset-row" data-pt="{{ $asset->company->code }}" data-status="{{ $asset->status }}">
                <td class="px-5 py-3 font-mono text-xs font-semibold text-gray-700">{{ $asset->tag_no }}</td>
                <td class="px-5 py-3 font-medium text-gray-800">{{ $asset->description }}</td>
                <td class="px-5 py-3 text-gray-500">{{ $asset->model ?? '—' }}</td>
                <td class="px-5 py-3 text-gray-500">{{ $asset->company->code }}</td>
                <td class="px-5 py-3 text-gray-500 capitalize">{{ $asset->type }}</td>
                <td class="px-5 py-3">
                    @php
                        $colors = [
                            'normal' => 'bg-green-100 text-green-700',
                            'warning' => 'bg-amber-100 text-amber-700',
                            'critical' => 'bg-orange-100 text-orange-700',
                            'breakdown' => 'bg-red-100 text-red-700'
                        ];
                    @endphp
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$asset->status] }}">
                        {{ ucfirst($asset->status) }}
                    </span>
                </td>
                <td class="px-5 py-3">
                    <form action="{{ route('assets.destroy', $asset) }}" method="POST"
                        onsubmit="return confirm('Hapus equipment ini?')">
                        @csrf @method('DELETE')
                        <button class="text-red-400 hover:text-red-600 text-xs">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-5 py-10 text-center text-gray-400">Belum ada data equipment.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal Tambah --}}
<div id="modal-add" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Tambah Equipment</h3>
            <button onclick="document.getElementById('modal-add').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form action="{{ route('assets.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">PT *</label>
                    <select name="company_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">Pilih PT</option>
                        @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->code }} — {{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Tag No *</label>
                    <input type="text" name="tag_no" required placeholder="SC14, P-6163P7"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Nama Equipment *</label>
                    <input type="text" name="name" required placeholder="Screw Feeder"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Model</label>
                    <input type="text" name="model" placeholder="Varmex HR97-37.08"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Brand</label>
                    <input type="text" name="brand"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Tipe</label>
                    <select name="type" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="rotating">Rotating</option>
                        <option value="static">Static</option>
                        <option value="electrical">Electrical</option>
                        <option value="instrument">Instrument</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Deskripsi</label>
                <textarea name="description" rows="2"
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
function filterPT(val) {
    document.querySelectorAll('.asset-row').forEach(row => {
        row.style.display = (!val || row.dataset.pt === val) ? '' : 'none';
    });
}
function filterStatus(val) {
    document.querySelectorAll('.asset-row').forEach(row => {
        row.style.display = (!val || row.dataset.status === val) ? '' : 'none';
    });
}
</script>

@endsection