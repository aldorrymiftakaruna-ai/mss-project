@extends('layouts.app')

@section('title', $sparepart->kode_material . ' — Detail Spare Part')
@section('page-title', $sparepart->kode_material)
@section('page-sub', '— ' . $sparepart->deskripsi)

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
    {{ session('success') }}
</div>
@endif

{{-- Back + Edit --}}
<div class="flex items-center justify-between mb-5">
    <a href="{{ route('spareparts.index') }}"
       class="text-sm text-gray-500 hover:text-gray-700">← Kembali ke Spare Part</a>
        <div class="flex gap-2">
        <button onclick="document.getElementById('modal-edit').classList.remove('hidden')"
            class="bg-[#0E9E8E] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#0a7a6d] transition">
            Edit Spare Part
        </button>
        <form action="{{ route('spareparts.destroy', $sparepart) }}" method="POST"
            onsubmit="return confirm('Hapus spare part {{ $sparepart->kode_material }}?')">
            @csrf @method('DELETE')
            <button class="border border-red-200 text-red-500 text-sm px-4 py-2 rounded-lg hover:bg-red-50 transition">
                Hapus
            </button>
        </form>
    </div>
</div>

{{-- ═══ SECTION 1 — Spesifikasi ═══ --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-800">Spesifikasi</h2>
        @php
            $colors = ['aman' => 'bg-green-100 text-green-700', 'kritis' => 'bg-amber-100 text-amber-700', 'habis' => 'bg-red-100 text-red-700'];
        @endphp
        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $colors[$sparepart->status] }}">
            {{ ucfirst($sparepart->status) }}
        </span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-x-8 gap-y-4 text-sm">
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Kode Material</p>
            <p class="font-mono font-semibold text-gray-800">{{ $sparepart->kode_material }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">PT</p>
            <p class="text-gray-700">{{ $sparepart->company->code ?? '—' }} — {{ $sparepart->company->name ?? '' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Deskripsi</p>
            <p class="text-gray-700">{{ $sparepart->deskripsi }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Satuan</p>
            <p class="text-gray-700">{{ $sparepart->satuan ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Stok Tersedia</p>
            <p class="text-gray-700 font-semibold">{{ $sparepart->stok_tersedia }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Stok Minimum</p>
            <p class="text-gray-700">{{ $sparepart->stok_minimum }}</p>
        </div>
    </div>
</div>

{{-- ═══ SECTION 2 — Foto / Gambar ═══ --}}
<div class="bg-white rounded-xl border border-gray-200 mb-6">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Foto & Gambar</h2>
        <button onclick="document.getElementById('modal-upload').classList.remove('hidden')"
            class="text-sm text-[#0E9E8E] hover:underline">+ Upload</button>
    </div>

    @if($sparepart->images->isEmpty())
        <p class="px-6 py-8 text-center text-gray-400 text-sm">Belum ada foto atau gambar.</p>
    @else
    <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($sparepart->images as $img)
        <div class="relative group">
            @if(str_ends_with(strtolower($img->path), '.pdf'))
                {{-- PDF — tampilkan icon --}}
                <a href="{{ $img->url }}" target="_blank"
                   class="flex flex-col items-center justify-center h-36 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition">
                    <svg class="w-10 h-10 text-red-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-xs text-gray-500">PDF</span>
                </a>
            @else
                {{-- Image --}}
                <a href="{{ $img->url }}" target="_blank">
                    <img src="{{ $img->url }}" alt="{{ $img->label }}"
                         class="w-full h-36 object-cover rounded-lg border border-gray-200 hover:opacity-90 transition">
                </a>
            @endif

            {{-- Label --}}
            @if($img->label)
            <p class="text-xs text-gray-500 mt-1 text-center truncate">{{ $img->label }}</p>
            @endif

            {{-- Tombol hapus --}}
            <form action="{{ route('spareparts.images.delete', [$sparepart, $img]) }}" method="POST"
                onsubmit="return confirm('Hapus gambar ini?')"
                class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition">
                @csrf @method('DELETE')
                <button class="bg-red-500 text-white rounded-full w-6 h-6 text-xs flex items-center justify-center hover:bg-red-600">✕</button>
            </form>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- ═══ SECTION 3 — Equipment yang pakai part ini (Reverse BOM) ═══ --}}
<div class="bg-white rounded-xl border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Dipakai di Equipment</h2>
    </div>

    @if($sparepart->assets->isEmpty())
        <p class="px-6 py-8 text-center text-gray-400 text-sm">Belum ada equipment yang menggunakan part ini.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr class="text-xs text-gray-500 uppercase">
                    <th class="px-5 py-3 text-left">Tag No</th>
                    <th class="px-5 py-3 text-left">Nama Equipment</th>
                    <th class="px-5 py-3 text-left">PT</th>
                    <th class="px-5 py-3 text-center">Qty Kebutuhan</th>
                    <th class="px-5 py-3 text-left">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($sparepart->assets as $asset)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <a href="{{ route('assets.show', $asset) }}"
                           class="font-mono text-xs font-semibold text-[#0E9E8E] hover:underline">
                            {{ $asset->tag_no }}
                        </a>
                    </td>
                    <td class="px-5 py-3 text-gray-800">{{ $asset->description }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $asset->company->code ?? '—' }}</td>
                    <td class="px-5 py-3 text-center text-gray-700">{{ $asset->pivot->jumlah_kebutuhan ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500 text-xs">{{ $asset->pivot->keterangan ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ═══ MODAL UPLOAD GAMBAR ═══ --}}
<div id="modal-upload" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Upload Foto / Gambar</h3>
            <button onclick="document.getElementById('modal-upload').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form action="{{ route('spareparts.images.upload', $sparepart) }}" method="POST"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Label (opsional)</label>
                <input type="text" name="label" placeholder="Foto Fisik / Drawing / Datasheet"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">File (JPG, PNG, PDF — maks 5MB per file)</label>
                <input type="file" name="images[]" multiple accept=".jpg,.jpeg,.png,.pdf"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">
                    Upload
                </button>
                <button type="button"
                    onclick="document.getElementById('modal-upload').classList.add('hidden')"
                    class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ═══ MODAL EDIT ═══ --}}
<div id="modal-edit" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Edit — {{ $sparepart->kode_material }}</h3>
            <button onclick="document.getElementById('modal-edit').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form action="{{ route('spareparts.update', $sparepart) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="text-xs text-gray-500 mb-1 block">PT *</label>
                <select name="company_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    @foreach(\App\Models\Company::all() as $co)
                    <option value="{{ $co->id }}" {{ $sparepart->company_id == $co->id ? 'selected' : '' }}>
                        {{ $co->code }} — {{ $co->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Kode Material *</label>
                    <input type="text" name="kode_material" required value="{{ $sparepart->kode_material }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Satuan</label>
                    <input type="text" name="satuan" value="{{ $sparepart->satuan }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Deskripsi *</label>
                <input type="text" name="deskripsi" required value="{{ $sparepart->deskripsi }}"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Stok Minimum</label>
                    <input type="number" name="stok_minimum" min="0" value="{{ $sparepart->stok_minimum }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Stok Tersedia</label>
                    <input type="number" name="stok_tersedia" min="0" value="{{ $sparepart->stok_tersedia }}"
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

@endsection