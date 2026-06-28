@extends('layouts.app')

@section('title', $asset->tag_no . ' — Detail Equipment')
@section('page-title', $asset->tag_no)
@section('page-sub', '— ' . $asset->description)

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
    {{ session('success') }}
</div>
@endif

{{-- Back + Edit --}}
<div class="flex items-center justify-between mb-5">
    <a href="{{ route('assets.index') }}"
       class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
        ← Kembali ke Equipment
    </a>
    <button onclick="document.getElementById('modal-edit').classList.remove('hidden')"
        class="bg-[#0E9E8E] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#0a7a6d] transition">
        Edit Equipment
    </button>
</div>

{{-- ═══════════════════════════════════════════════════
     SECTION 1 — Spesifikasi
════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-800">Spesifikasi Equipment</h2>
        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $asset->statusColor() }}">
            {{ ucfirst($asset->status) }}
        </span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-x-8 gap-y-4 text-sm">
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Tag No</p>
            <p class="font-mono font-semibold text-gray-800">{{ $asset->tag_no }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">PT</p>
            <p class="text-gray-700">{{ $asset->company->code }} — {{ $asset->company->name }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Deskripsi</p>
            <p class="text-gray-700">{{ $asset->description }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Model</p>
            <p class="text-gray-700">{{ $asset->model ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Serial Number</p>
            <p class="text-gray-700">{{ $asset->serial_number ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Head / Capacity</p>
            <p class="text-gray-700">{{ $asset->head_capacity ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Motor kW</p>
            <p class="text-gray-700">{{ $asset->motor_kw ? $asset->motor_kw . ' kW' : '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Motor RPM</p>
            <p class="text-gray-700">{{ $asset->motor_rpm ? number_format($asset->motor_rpm) . ' rpm' : '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Motor Ampere (FLA)</p>
            <p class="text-gray-700">{{ $asset->motor_ampere ? $asset->motor_ampere . ' A' : '—' }}</p>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════
     SECTION 2 — History CM
════════════════════════════════════════════════════ --}}
@php
    $thr = $asset->thresholds();
    $vibAlarm  = $thr['alarm'];
    $vibDanger = $thr['danger'];
    $tempDanger = $thr['tempDanger'];
@endphp

<div class="bg-white rounded-xl border border-gray-200 mb-6">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">History Condition Monitoring</h2>
        <span class="text-xs text-gray-400">
            Alarm ≥ {{ $vibAlarm }} mm/s &nbsp;|&nbsp;
            Danger ≥ {{ $vibDanger }} mm/s &nbsp;|&nbsp;
            Temp Danger ≥ {{ $tempDanger }}°C
        </span>
    </div>

    @if($asset->cmMeasurements->isEmpty())
        <p class="px-6 py-8 text-center text-gray-400 text-sm">Belum ada data CM.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr class="text-gray-500 uppercase">
                    <th class="px-4 py-2 text-left whitespace-nowrap">Tanggal</th>
                    <th class="px-4 py-2 text-left whitespace-nowrap">Diukur Oleh</th>
                    <th class="px-3 py-2 text-center border-l border-gray-100" colspan="4">Driver DE (V/H/A / CF+)</th>
                    <th class="px-3 py-2 text-center">Tmp</th>
                    <th class="px-3 py-2 text-center border-l border-gray-100" colspan="4">Driver NDE (V/H/A / CF+)</th>
                    <th class="px-3 py-2 text-center">Tmp</th>
                    <th class="px-3 py-2 text-center">Amp</th>
                    <th class="px-3 py-2 text-center border-l border-gray-100" colspan="4">Driven DE (V/H/A / CF+)</th>
                    <th class="px-3 py-2 text-center">Tmp</th>
                    <th class="px-3 py-2 text-center border-l border-gray-100" colspan="4">Driven NDE (V/H/A / CF+)</th>
                    <th class="px-3 py-2 text-center">Tmp</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($asset->cmMeasurements as $cm)
                @php
                    // Helper inline — warna cell
                    $vc = function($val) use ($vibAlarm, $vibDanger) {
                        if ($val === null || $val === '') return '';
                        $v = (float) $val;
                        if ($v >= $vibDanger) return 'bg-red-100 text-red-700 font-semibold';
                        if ($v >= $vibAlarm)  return 'bg-amber-100 text-amber-700 font-semibold';
                        return '';
                    };
                    $tc = function($val) use ($tempDanger) {
                        if ($val === null || $val === '') return '';
                        return (float)$val >= $tempDanger ? 'bg-red-100 text-red-700 font-semibold' : '';
                    };
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 whitespace-nowrap text-gray-700">
                        {{ \Carbon\Carbon::parse($cm->tanggal)->format('d M Y') }}
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap text-gray-600">{{ $cm->measured_by }}</td>

                    {{-- Driver DE --}}
                    <td class="px-3 py-2 text-center border-l border-gray-100 {{ $vc($cm->driver_de_vib_v) }}">{{ $cm->driver_de_vib_v ?? '—' }}</td>
                    <td class="px-3 py-2 text-center {{ $vc($cm->driver_de_vib_h) }}">{{ $cm->driver_de_vib_h ?? '—' }}</td>
                    <td class="px-3 py-2 text-center {{ $vc($cm->driver_de_vib_a) }}">{{ $cm->driver_de_vib_a ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ $cm->driver_de_cf ?? '—' }}</td>
                    <td class="px-3 py-2 text-center {{ $tc($cm->driver_de_temp) }}">{{ $cm->driver_de_temp ?? '—' }}</td>

                    {{-- Driver NDE --}}
                    <td class="px-3 py-2 text-center border-l border-gray-100 {{ $vc($cm->driver_nde_vib_v) }}">{{ $cm->driver_nde_vib_v ?? '—' }}</td>
                    <td class="px-3 py-2 text-center {{ $vc($cm->driver_nde_vib_h) }}">{{ $cm->driver_nde_vib_h ?? '—' }}</td>
                    <td class="px-3 py-2 text-center {{ $vc($cm->driver_nde_vib_a) }}">{{ $cm->driver_nde_vib_a ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ $cm->driver_nde_cf ?? '—' }}</td>
                    <td class="px-3 py-2 text-center {{ $tc($cm->driver_nde_temp) }}">{{ $cm->driver_nde_temp ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ $cm->driver_ampere ?? '—' }}</td>

                    {{-- Driven DE --}}
                    <td class="px-3 py-2 text-center border-l border-gray-100 {{ $vc($cm->driven_de_vib_v) }}">{{ $cm->driven_de_vib_v ?? '—' }}</td>
                    <td class="px-3 py-2 text-center {{ $vc($cm->driven_de_vib_h) }}">{{ $cm->driven_de_vib_h ?? '—' }}</td>
                    <td class="px-3 py-2 text-center {{ $vc($cm->driven_de_vib_a) }}">{{ $cm->driven_de_vib_a ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ $cm->driven_de_cf ?? '—' }}</td>
                    <td class="px-3 py-2 text-center {{ $tc($cm->driven_de_temp) }}">{{ $cm->driven_de_temp ?? '—' }}</td>

                    {{-- Driven NDE --}}
                    <td class="px-3 py-2 text-center border-l border-gray-100 {{ $vc($cm->driven_nde_vib_v) }}">{{ $cm->driven_nde_vib_v ?? '—' }}</td>
                    <td class="px-3 py-2 text-center {{ $vc($cm->driven_nde_vib_h) }}">{{ $cm->driven_nde_vib_h ?? '—' }}</td>
                    <td class="px-3 py-2 text-center {{ $vc($cm->driven_nde_vib_a) }}">{{ $cm->driven_nde_vib_a ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">{{ $cm->driven_nde_cf ?? '—' }}</td>
                    <td class="px-3 py-2 text-center {{ $tc($cm->driven_nde_temp) }}">{{ $cm->driven_nde_temp ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════
     SECTION 3 — CM Findings
     Kolom aktual: reported_by, tanggal, kategori,
                   deskripsi, severity, status, foto_path
════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Temuan Visual / CM Findings</h2>
    </div>

    @if($asset->cmFindings->isEmpty())
        <p class="px-6 py-8 text-center text-gray-400 text-sm">Belum ada temuan CM.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr class="text-xs text-gray-500 uppercase">
                    <th class="px-5 py-3 text-left">Tanggal</th>
                    <th class="px-5 py-3 text-left">Dilaporkan Oleh</th>
                    <th class="px-5 py-3 text-left">Kategori</th>
                    <th class="px-5 py-3 text-left">Deskripsi Temuan</th>
                    <th class="px-5 py-3 text-center">Severity</th>
                    <th class="px-5 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($asset->cmFindings->take(15) as $finding)
                @php
                    $sevColor = match(strtolower($finding->severity ?? '')) {
                        'high', 'tinggi'     => 'bg-red-100 text-red-700',
                        'medium', 'sedang'   => 'bg-amber-100 text-amber-700',
                        default              => 'bg-green-100 text-green-700',
                    };
                    $stColor = match(strtolower($finding->status ?? '')) {
                        'open'               => 'bg-red-100 text-red-700',
                        'in_progress'        => 'bg-amber-100 text-amber-700',
                        'closed', 'selesai'  => 'bg-green-100 text-green-700',
                        default              => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 whitespace-nowrap text-gray-700">
                        {{ \Carbon\Carbon::parse($finding->tanggal)->format('d M Y') }}
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $finding->reported_by ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600 capitalize">{{ $finding->kategori ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $finding->deskripsi ?? '—' }}</td>
                    <td class="px-5 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sevColor }}">
                            {{ ucfirst($finding->severity ?? '—') }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $stColor }}">
                            {{ ucfirst($finding->status ?? '—') }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════
     SECTION 4 — Maintenance Reports
     Kolom aktual: reported_by, shift, tanggal, jenis,
                   deskripsi_masalah, tindakan, status,
                   foto_path, catatan
════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">History Maintenance Reports</h2>
    </div>

    @if($asset->maintenanceReports->isEmpty())
        <p class="px-6 py-8 text-center text-gray-400 text-sm">Belum ada laporan maintenance.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr class="text-xs text-gray-500 uppercase">
                    <th class="px-5 py-3 text-left">Tanggal</th>
                    <th class="px-5 py-3 text-left">Shift</th>
                    <th class="px-5 py-3 text-left">Jenis</th>
                    <th class="px-5 py-3 text-left">Masalah</th>
                    <th class="px-5 py-3 text-left">Tindakan</th>
                    <th class="px-5 py-3 text-left">Dilaporkan Oleh</th>
                    <th class="px-5 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($asset->maintenanceReports as $report)
                @php
                    $sc = match(strtolower($report->status ?? '')) {
                        'selesai', 'completed' => 'bg-green-100 text-green-700',
                        'in_progress'          => 'bg-amber-100 text-amber-700',
                        'open', 'pending'      => 'bg-red-100 text-red-700',
                        default                => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 whitespace-nowrap text-gray-700">
                        {{ \Carbon\Carbon::parse($report->tanggal)->format('d M Y') }}
                    </td>
                    <td class="px-5 py-3 text-gray-600 capitalize">{{ $report->shift ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600 capitalize">{{ $report->jenis ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-700 max-w-[200px] truncate" title="{{ $report->deskripsi_masalah }}">
                        {{ $report->deskripsi_masalah ?? '—' }}
                    </td>
                    <td class="px-5 py-3 text-gray-600 max-w-[180px] truncate" title="{{ $report->tindakan }}">
                        {{ $report->tindakan ?? '—' }}
                    </td>
                    <td class="px-5 py-3 text-gray-600">{{ $report->reported_by ?? '—' }}</td>
                    <td class="px-5 py-3 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sc }}">
                            {{ ucfirst($report->status ?? '—') }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════
     SECTION 5 — BOM Spare Part
     Kolom aktual: kode_material, deskripsi, satuan,
                   stok_minimum, stok_tersedia, kategori
════════════════════════════════════════════════════ --}}
<div class="bg-white rounded-xl border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">BOM Spare Part</h2>
    </div>

    @if($asset->spareParts->isEmpty())
        <p class="px-6 py-8 text-center text-gray-400 text-sm">Belum ada spare part terdaftar.</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr class="text-xs text-gray-500 uppercase">
                    <th class="px-5 py-3 text-left">Kode Material</th>
                    <th class="px-5 py-3 text-left">Deskripsi Part</th>
                    <th class="px-5 py-3 text-left">Kategori</th>
                    <th class="px-5 py-3 text-center">Satuan</th>
                    <th class="px-5 py-3 text-center">Qty Kebutuhan</th>
                    <th class="px-5 py-3 text-center">Stok Tersedia</th>
                    <th class="px-5 py-3 text-center">Stok Min</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($asset->spareParts as $part)
                @php
                    $stokOk = $part->stok_tersedia >= ($part->pivot->jumlah_kebutuhan ?? 0);
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 font-mono text-xs font-semibold text-gray-700">
                        {{ $part->kode_material ?? '—' }}
                    </td>
                    <td class="px-5 py-3 text-gray-800">{{ $part->deskripsi ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500 capitalize">{{ $part->kategori ?? '—' }}</td>
                    <td class="px-5 py-3 text-center text-gray-600">{{ $part->satuan ?? '—' }}</td>
                    <td class="px-5 py-3 text-center text-gray-700">
                        {{ $part->pivot->jumlah_kebutuhan ?? '—' }}
                    </td>
                    <td class="px-5 py-3 text-center font-medium {{ $stokOk ? 'text-green-600' : 'text-red-600' }}">
                        {{ $part->stok_tersedia ?? 0 }}
                    </td>
                    <td class="px-5 py-3 text-center text-gray-500">{{ $part->stok_minimum ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- ═══════════════════════════════════════════════════
     MODAL EDIT
════════════════════════════════════════════════════ --}}
<div id="modal-edit" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Edit Equipment — {{ $asset->tag_no }}</h3>
            <button onclick="document.getElementById('modal-edit').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
        </div>
        <form action="{{ route('assets.update', $asset) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">PT *</label>
                    <select name="company_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        @foreach(\App\Models\Company::all() as $co)
                        <option value="{{ $co->id }}" {{ $asset->company_id == $co->id ? 'selected' : '' }}>
                            {{ $co->code }} — {{ $co->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Tag No *</label>
                    <input type="text" name="tag_no" required value="{{ $asset->tag_no }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="text-xs text-gray-500 mb-1 block">Nama / Deskripsi *</label>
                    <input type="text" name="description" required value="{{ $asset->description }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Model</label>
                    <input type="text" name="model" value="{{ $asset->model }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Serial Number</label>
                    <input type="text" name="serial_number" value="{{ $asset->serial_number }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Head / Capacity</label>
                    <input type="text" name="head_capacity" value="{{ $asset->head_capacity }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Motor kW</label>
                    <input type="number" step="0.01" name="motor_kw" value="{{ $asset->motor_kw }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Motor RPM</label>
                    <input type="number" name="motor_rpm" value="{{ $asset->motor_rpm }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Motor Ampere (FLA)</label>
                    <input type="number" step="0.01" name="motor_ampere" value="{{ $asset->motor_ampere }}"
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