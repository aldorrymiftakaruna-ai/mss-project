@extends('layouts.app')

@section('title', $cmFinding->finding_code)
@section('page-title', $cmFinding->finding_code)
@section('page-sub', '— ' . ($cmFinding->asset->tag_no ?? '—') . ' · ' . ($cmFinding->asset->description ?? '—'))

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif

@php
use Illuminate\Support\Facades\Storage;
$scColors = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-amber-100 text-amber-700','high'=>'bg-red-100 text-red-700'];
$stColors = ['open'=>'bg-red-100 text-red-700','closed'=>'bg-green-100 text-green-700'];
$fotos = collect([$cmFinding->foto_path, $cmFinding->foto_path_2, $cmFinding->foto_path_3])->filter();
$isClosed = $cmFinding->status === 'closed';
@endphp

<div class="flex items-center justify-between mb-6">
    <a href="{{ route('cm.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Kembali ke Condition Monitoring</a>
    <div class="flex gap-2">
        <button onclick="document.getElementById('modal-edit').classList.remove('hidden')" class="bg-[#0E9E8E] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#0a7a6d] transition">Edit Temuan</button>
        <button onclick="document.getElementById('modal-closing').classList.remove('hidden')" class="border border-[#0E9E8E] text-[#0E9E8E] text-sm px-4 py-2 rounded-lg hover:bg-teal-50 transition {{ $isClosed ? 'opacity-50 cursor-not-allowed' : '' }}" {{ $isClosed ? 'disabled' : '' }}>
            {{ $isClosed ? 'Sudah Closed' : 'Closing' }}
        </button>
        <form action="{{ route('cm.findings.destroy', $cmFinding) }}" method="POST" onsubmit="return confirm('Hapus temuan {{ $cmFinding->finding_code }}? Tindakan ini tidak bisa dibatalkan.');">
            @csrf @method('DELETE')
            <button type="submit" class="border border-red-200 text-red-500 text-sm px-4 py-2 rounded-lg hover:bg-red-50 transition">Hapus</button>
        </form>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="font-semibold text-gray-800">{{ $cmFinding->asset->tag_no ?? '—' }}</h3>
            <p class="text-xs text-gray-400">{{ $cmFinding->asset->description ?? '—' }} &middot; {{ $cmFinding->asset->company->code ?? '—' }}</p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ $cmFinding->finding_code }}</span>
    </div>

    <div class="flex gap-2 flex-wrap mb-5">
        @if($cmFinding->severity)
        <span class="px-3 py-1 rounded-full text-xs font-medium {{ $scColors[$cmFinding->severity] ?? 'bg-gray-100' }}">{{ ucfirst($cmFinding->severity) }}</span>
        @endif
        <span class="px-3 py-1 rounded-full text-xs font-medium {{ $stColors[$cmFinding->status] ?? 'bg-gray-100' }}">{{ ucfirst($cmFinding->status) }}</span>
        <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ $cmFinding->kategori }}</span>
        <span class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ $cmFinding->tanggal->format('d M Y') }}</span>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-5">
        <div>
            <p class="text-xs text-gray-400 mb-1">Analysis</p>
            <p class="text-sm text-gray-700">{{ $cmFinding->analysis ?: '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-1">Action</p>
            <p class="text-sm text-gray-700">{{ $cmFinding->action ?: '—' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 mb-5">
        <div>
            <p class="text-xs text-gray-400 mb-1">PIC</p>
            <p class="text-sm text-gray-700">{{ $cmFinding->pic ?: '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-1">Date Action</p>
            <p class="text-sm text-gray-700">{{ $cmFinding->date_action ? $cmFinding->date_action->format('d M Y') : '—' }}</p>
        </div>
    </div>

    @if($cmFinding->remark)
    <div class="mb-5">
        <p class="text-xs text-gray-400 mb-1">Remark</p>
        <p class="text-sm text-gray-700">{{ $cmFinding->remark }}</p>
    </div>
    @endif

    @if($fotos->isNotEmpty())
    <div>
        <p class="text-xs text-gray-400 mb-2">Foto Temuan</p>
        <div class="flex gap-3 flex-wrap">
            @foreach($fotos as $foto)
            <a href="{{ Storage::url($foto) }}" target="_blank">
                <img src="{{ Storage::url($foto) }}" class="h-28 w-28 object-cover rounded-lg border border-gray-200">
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- ─── Modal Edit Temuan ─── --}}
<div id="modal-edit" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Edit Temuan Visual — {{ $cmFinding->finding_code }}</h3>
            <button onclick="document.getElementById('modal-edit').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form action="{{ route('cm.findings.update', $cmFinding) }}" method="POST" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Equipment *</label>
                    <select name="asset_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        @foreach($assets as $asset)
                        <option value="{{ $asset->id }}" {{ $asset->id == $cmFinding->asset_id ? 'selected' : '' }}>{{ $asset->tag_no }} — {{ $asset->description }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Tanggal *</label>
                    <input type="date" name="tanggal" required value="{{ $cmFinding->tanggal->format('Y-m-d') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Kategori *</label>
                    <input type="text" name="kategori" required value="{{ $cmFinding->kategori }}" placeholder="mis. Leak, Noise, Corrosion" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Severity</label>
                    <select name="severity" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">— (belum ditentukan)</option>
                        <option value="low" {{ $cmFinding->severity == 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ $cmFinding->severity == 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ $cmFinding->severity == 'high' ? 'selected' : '' }}>High</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">PIC</label>
                    <select name="pic" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">— Pilih PIC —</option>
                        @foreach(['Mechanic', 'Electric', 'Production'] as $opt)
                        <option value="{{ $opt }}" {{ $cmFinding->pic == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Status *</label>
                    <select name="status" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="open" {{ $cmFinding->status == 'open' ? 'selected' : '' }}>Open</option>
                        <option value="closed" {{ $cmFinding->status == 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Analysis</label>
                <textarea name="analysis" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">{{ $cmFinding->analysis }}</textarea>
            </div>

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Remark</label>
                <textarea name="remark" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">{{ $cmFinding->remark }}</textarea>
            </div>

            <div class="border border-gray-100 rounded-lg p-3 space-y-2">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Foto (opsional — kosongkan jika tidak diganti)</p>
                <div class="flex gap-3 flex-wrap mb-2">
                    @foreach($fotos as $foto)
                    <img src="{{ Storage::url($foto) }}" class="h-20 w-20 object-cover rounded-lg border border-gray-200">
                    @endforeach
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="text-xs text-gray-400 block mb-1">Foto 1</label>
                        <input type="file" name="foto_1" accept="image/*" class="w-full text-xs border border-gray-200 rounded px-2 py-1.5">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 block mb-1">Foto 2</label>
                        <input type="file" name="foto_2" accept="image/*" class="w-full text-xs border border-gray-200 rounded px-2 py-1.5">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 block mb-1">Foto 3</label>
                        <input type="file" name="foto_3" accept="image/*" class="w-full text-xs border border-gray-200 rounded px-2 py-1.5">
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">Simpan Perubahan</button>
                <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')" class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- ─── Modal Closing ─── --}}
<div id="modal-closing" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Closing Temuan — {{ $cmFinding->finding_code }}</h3>
            <button onclick="document.getElementById('modal-closing').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form action="{{ route('cm.findings.close', $cmFinding) }}" method="POST" class="px-6 py-5 space-y-4">
            @csrf

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Action <span class="text-gray-300">(tindakan yg dilakukan)</span></label>
                <textarea name="action" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">{{ old('action', $cmFinding->action) }}</textarea>
            </div>

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Date Action</label>
                <input type="date" name="date_action" value="{{ old('date_action', $cmFinding->date_action ? $cmFinding->date_action->format('Y-m-d') : '') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Remark <span class="text-gray-300">(opsional — catatan penutupan)</span></label>
                <textarea name="remark" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">{{ old('remark', $cmFinding->remark) }}</textarea>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 text-sm text-amber-700">
                Status temuan akan otomatis berubah menjadi <strong>Closed</strong> setelah closing disimpan.
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">Simpan Closing</button>
                <button type="button" onclick="document.getElementById('modal-closing').classList.add('hidden')" class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">Batal</button>
            </div>
        </form>
    </div>
</div>

@endsection
