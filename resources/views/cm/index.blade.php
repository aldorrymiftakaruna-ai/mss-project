@extends('layouts.app')

@section('title', 'Condition Monitoring')
@section('page-title', 'Condition Monitoring')
@section('page-sub', '— Data pengukuran & temuan visual')

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
    {{ session('success') }}
</div>
@endif

{{-- Tab --}}
<div class="flex gap-2 mb-5">
    <button onclick="switchTab('measurements')" id="tab-measurements"
        class="px-4 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium transition">
        Pengukuran
    </button>
    <button onclick="switchTab('findings')" id="tab-findings"
        class="px-4 py-2 text-sm rounded-lg bg-white border border-gray-200 text-gray-600 font-medium transition">
        Temuan Visual
    </button>
    <div class="ml-auto">
        <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
            class="bg-[#0E9E8E] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#0a7a6d] transition">
            + Tambah Data CM
        </button>
    </div>
</div>

{{-- Tab Pengukuran --}}
<div id="content-measurements">
    <div class="bg-white rounded-xl border border-gray-200">
        <table class="w-full text-sm">
    <thead class="border-b border-gray-100">
        <tr class="text-xs text-gray-500 uppercase">
            <th class="px-5 py-3 text-left">Tanggal</th>
            <th class="px-5 py-3 text-left">Equipment</th>
            <th class="px-5 py-3 text-left">Driver Max Vib (mm/s)</th>
            <th class="px-5 py-3 text-left">Driver Max Temp (°C)</th>
            <th class="px-5 py-3 text-left">Driven Max Vib (mm/s)</th>
            <th class="px-5 py-3 text-left">Driven Max Temp (°C)</th>
            <th class="px-5 py-3 text-left">Ampere (A)</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-50">
        @forelse($measurements as $m)
        @php
            $driverMaxVib = max(
                $m->driver_de_vib_v ?? 0, $m->driver_de_vib_h ?? 0, $m->driver_de_vib_a ?? 0,
                $m->driver_nde_vib_v ?? 0, $m->driver_nde_vib_h ?? 0, $m->driver_nde_vib_a ?? 0
            );
            $driverMaxTemp = max($m->driver_de_temp ?? 0, $m->driver_nde_temp ?? 0);
            $drivenMaxVib = max(
                $m->driven_de_vib_v ?? 0, $m->driven_de_vib_h ?? 0, $m->driven_de_vib_a ?? 0,
                $m->driven_nde_vib_v ?? 0, $m->driven_nde_vib_h ?? 0, $m->driven_nde_vib_a ?? 0
            );
            $drivenMaxTemp = max($m->driven_de_temp ?? 0, $m->driven_nde_temp ?? 0);
        @endphp
        <tr class="hover:bg-gray-50">
            <td class="px-5 py-3 text-gray-600">{{ $m->tanggal->format('d M Y') }}</td>
            <td class="px-5 py-3">
                <div class="font-medium text-gray-800">{{ $m->asset->name }}</div>
                <div class="text-xs text-gray-400 font-mono">{{ $m->asset->tag_no }}</div>
            </td>
            <td class="px-5 py-3 {{ $driverMaxVib > 4.5 ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                {{ $driverMaxVib > 0 ? number_format($driverMaxVib, 2) : '—' }}
            </td>
            <td class="px-5 py-3 {{ $driverMaxTemp > 82 ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                {{ $driverMaxTemp > 0 ? number_format($driverMaxTemp, 1) : '—' }}
            </td>
            <td class="px-5 py-3 {{ $drivenMaxVib > 4.5 ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                {{ $drivenMaxVib > 0 ? number_format($drivenMaxVib, 2) : '—' }}
            </td>
            <td class="px-5 py-3 {{ $drivenMaxTemp > 82 ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                {{ $drivenMaxTemp > 0 ? number_format($drivenMaxTemp, 1) : '—' }}
            </td>
            <td class="px-5 py-3 text-gray-600">{{ $m->driver_ampere ?? '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="7" class="px-5 py-10 text-center text-gray-400">Belum ada data pengukuran.</td>
        </tr>
        @endforelse
    </tbody>
</table>
    </div>
</div>

{{-- Tab Temuan Visual --}}
<div id="content-findings" class="hidden">
    <div class="bg-white rounded-xl border border-gray-200">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-100">
                <tr class="text-xs text-gray-500 uppercase">
                    <th class="px-5 py-3 text-left">Tanggal</th>
                    <th class="px-5 py-3 text-left">Equipment</th>
                    <th class="px-5 py-3 text-left">Kategori</th>
                    <th class="px-5 py-3 text-left">Deskripsi</th>
                    <th class="px-5 py-3 text-left">Severity</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-left">Dilaporkan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($findings as $f)
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-600">{{ $f->tanggal->format('d M Y') }}</td>
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-800">{{ $f->asset->name }}</div>
                        <div class="text-xs text-gray-400 font-mono">{{ $f->asset->tag_no }}</div>
                    </td>
                    <td class="px-5 py-3 capitalize text-gray-600">{{ str_replace('_', ' ', $f->kategori) }}</td>
                    <td class="px-5 py-3 text-gray-600 max-w-xs truncate">{{ $f->deskripsi }}</td>
                    <td class="px-5 py-3">
                        @php $sc = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-amber-100 text-amber-700','high'=>'bg-red-100 text-red-700']; @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sc[$f->severity] }}">
                            {{ ucfirst($f->severity) }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        @php $st = ['open'=>'bg-red-100 text-red-700','acknowledged'=>'bg-amber-100 text-amber-700','resolved'=>'bg-green-100 text-green-700']; @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $st[$f->status] }}">
                            {{ ucfirst($f->status) }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $f->reporter->name ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-10 text-center text-gray-400">Belum ada temuan visual.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Tambah --}}
<div id="modal-add" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-lg p-6 max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Tambah Data CM</h3>
            <button onclick="document.getElementById('modal-add').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        {{-- Type Toggle --}}
        <div class="flex gap-2 mb-4">
            <button type="button" onclick="switchType('measurement')" id="type-measurement"
                class="flex-1 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium">
                Pengukuran
            </button>
            <button type="button" onclick="switchType('finding')" id="type-finding"
                class="flex-1 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 font-medium">
                Temuan Visual
            </button>
        </div>

        <form action="{{ route('cm.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="type" id="input-type" value="measurement">

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
                    <label class="text-xs text-gray-500 mb-1 block">Tanggal *</label>
                    <input type="date" name="tanggal" required value="{{ date('Y-m-d') }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            {{-- Measurement Fields --}}
<div id="fields-measurement" class="space-y-4">
    <div>
        <label class="text-xs text-gray-500 mb-1 block">Diukur Oleh *</label>
        <select name="measured_by" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            <option value="">Pilih Teknisi</option>
            @foreach($employees as $emp)
            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- Driver (Motor) --}}
    <div class="border border-gray-100 rounded-lg p-3">
        <p class="text-xs font-semibold text-gray-600 mb-3 uppercase tracking-wider">Driver (Motor)</p>
        <div class="grid grid-cols-3 gap-2 mb-2">
            <div>
                <label class="text-xs text-gray-400 mb-1 block">DE Vib V (mm/s)</label>
                <input type="number" step="0.01" name="driver_de_vib_v" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">DE Vib H (mm/s)</label>
                <input type="number" step="0.01" name="driver_de_vib_h" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">DE Vib A (mm/s)</label>
                <input type="number" step="0.01" name="driver_de_vib_a" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">DE CF+</label>
                <input type="number" step="0.01" name="driver_de_cf" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">DE Temp (°C)</label>
                <input type="number" step="0.01" name="driver_de_temp" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2 mb-2">
            <div>
                <label class="text-xs text-gray-400 mb-1 block">NDE Vib V (mm/s)</label>
                <input type="number" step="0.01" name="driver_nde_vib_v" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">NDE Vib H (mm/s)</label>
                <input type="number" step="0.01" name="driver_nde_vib_h" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">NDE Vib A (mm/s)</label>
                <input type="number" step="0.01" name="driver_nde_vib_a" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">NDE CF+</label>
                <input type="number" step="0.01" name="driver_nde_cf" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">NDE Temp (°C)</label>
                <input type="number" step="0.01" name="driver_nde_temp" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">Ampere (A)</label>
                <input type="number" step="0.01" name="driver_ampere" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
        </div>
    </div>

    {{-- Driven --}}
    <div class="border border-gray-100 rounded-lg p-3">
        <p class="text-xs font-semibold text-gray-600 mb-3 uppercase tracking-wider">Driven (Gearbox/Pump/dll)</p>
        <div class="grid grid-cols-3 gap-2 mb-2">
            <div>
                <label class="text-xs text-gray-400 mb-1 block">DE Vib V (mm/s)</label>
                <input type="number" step="0.01" name="driven_de_vib_v" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">DE Vib H (mm/s)</label>
                <input type="number" step="0.01" name="driven_de_vib_h" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">DE Vib A (mm/s)</label>
                <input type="number" step="0.01" name="driven_de_vib_a" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">DE CF+</label>
                <input type="number" step="0.01" name="driven_de_cf" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">DE Temp (°C)</label>
                <input type="number" step="0.01" name="driven_de_temp" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
        </div>
        <div class="grid grid-cols-3 gap-2">
            <div>
                <label class="text-xs text-gray-400 mb-1 block">NDE Vib V (mm/s)</label>
                <input type="number" step="0.01" name="driven_nde_vib_v" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">NDE Vib H (mm/s)</label>
                <input type="number" step="0.01" name="driven_nde_vib_h" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">NDE Vib A (mm/s)</label>
                <input type="number" step="0.01" name="driven_nde_vib_a" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">NDE CF+</label>
                <input type="number" step="0.01" name="driven_nde_cf" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
            <div>
                <label class="text-xs text-gray-400 mb-1 block">NDE Temp (°C)</label>
                <input type="number" step="0.01" name="driven_nde_temp" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs">
            </div>
        </div>
    </div>

    <div>
        <label class="text-xs text-gray-500 mb-1 block">Catatan</label>
        <textarea name="catatan" rows="2"
            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></textarea>
    </div>
</div>

            {{-- Finding Fields --}}
            <div id="fields-finding" class="space-y-4 hidden">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Dilaporkan Oleh *</label>
                    <select name="reported_by" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">Pilih Teknisi</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Kategori</label>
                        <select name="kategori" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                            <option value="korosi">Korosi</option>
                            <option value="kebocoran">Kebocoran</option>
                            <option value="baut_loose">Baut Loose</option>
                            <option value="guard_lepas">Guard Lepas</option>
                            <option value="abnormal_suara">Abnormal Suara</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Severity</label>
                        <select name="severity" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Deskripsi</label>
                    <textarea name="deskripsi" rows="2"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></textarea>
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
function switchTab(tab) {
    document.getElementById('content-measurements').classList.toggle('hidden', tab !== 'measurements');
    document.getElementById('content-findings').classList.toggle('hidden', tab !== 'findings');
    document.getElementById('tab-measurements').className = tab === 'measurements'
        ? 'px-4 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium transition'
        : 'px-4 py-2 text-sm rounded-lg bg-white border border-gray-200 text-gray-600 font-medium transition';
    document.getElementById('tab-findings').className = tab === 'findings'
        ? 'px-4 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium transition'
        : 'px-4 py-2 text-sm rounded-lg bg-white border border-gray-200 text-gray-600 font-medium transition';
}

function switchType(type) {
    document.getElementById('input-type').value = type;
    document.getElementById('fields-measurement').classList.toggle('hidden', type !== 'measurement');
    document.getElementById('fields-finding').classList.toggle('hidden', type !== 'finding');
    document.getElementById('type-measurement').className = type === 'measurement'
        ? 'flex-1 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium'
        : 'flex-1 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 font-medium';
    document.getElementById('type-finding').className = type === 'finding'
        ? 'flex-1 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium'
        : 'flex-1 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 font-medium';
}
</script>

@endsection