@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-sub', '— Ringkasan hari ini')

@section('content')

{{-- KPI Cards --}}
<div class="grid grid-cols-6 gap-4 mb-6">
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Equipment</div>
        <div class="text-3xl font-bold text-gray-900">{{ $totalAssets }}</div>
        <div class="text-xs text-gray-400 mt-1">TERDAFTAR</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Equipment Danger</div>
        <div class="text-3xl font-bold text-red-500">{{ $equipmentDanger }}</div>
        <div class="text-xs text-gray-400 mt-1">BUTUH TINDAKAN</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Laporan Hari Ini</div>
        <div class="text-3xl font-bold text-[#0E9E8E]">{{ $laporanHariIni }}</div>
        <div class="text-xs text-gray-400 mt-1">MASUK</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Downtime Bulan Ini</div>
        <div class="text-2xl font-bold text-orange-600">{{ round($totalDowntimeBulanIni / 60, 1) }} jam</div>
        <div class="text-xs text-gray-400 mt-1">{{ $totalDowntimeBulanIni }} MENIT</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Lembur Bulan Ini</div>
        <div class="text-2xl font-bold text-purple-600">{{ $totalLemburBulanIni }} jam</div>
        <div class="text-xs text-gray-400 mt-1">{{ $totalLaporanLemburBulanIni }} LAPORAN</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Jam Kerja (Minggu Ini)</div>
        <div class="text-2xl font-bold text-gray-700">{{ $totalJamMingguIni }} <span class="text-sm font-normal text-gray-400">jam</span></div>
        <div class="text-xs text-gray-400 mt-1">{{ $totalLaporanMinggu }} LAPORAN</div>
    </div>
</div>

{{-- Grid Utama --}}
<div class="grid grid-cols-3 gap-5">

    {{-- KIRI: 2/3 — Mini Chart, Top 5, CM Alert --}}
    <div class="col-span-2 space-y-5">

        {{-- Mini Chart 7 Hari --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Tren Laporan 7 Hari</h2>
            </div>
            <div class="p-5">
                <div class="flex items-end gap-2 h-28">
                    @foreach($chart7Hari['data'] as $i => $val)
                        @php
                            $maxVal = max(max($chart7Hari['data']), 1);
                            $height = max(round(($val / $maxVal) * 100), 4);
                        @endphp
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <span class="text-xs font-semibold text-gray-600">{{ $val }}</span>
                            <div class="w-full rounded-md bg-[#0E9E8E]/20 relative" style="height: 92px;">
                                <div class="absolute bottom-0 w-full rounded-md bg-[#0E9E8E] transition-all" style="height: {{ $height }}%;"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 mt-1">{{ $chart7Hari['labels'][$i] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Top 5 Equipment Rusak Bulan Ini --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Top 5 Equipment Rusak Bulan Ini</h2>
                <a href="{{ route('maintenance.index') }}" class="text-xs text-[#0E9E8E] hover:underline">Lihat semua →</a>
            </div>
            <div class="p-5">
                @if($topEquipmentRusak->isEmpty())
                    <p class="text-gray-400 text-sm text-center py-6">Belum ada data maintenance bulan ini.</p>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500 uppercase border-b border-gray-100">
                                <th class="pb-2 text-left">#</th>
                                <th class="pb-2 text-left">Tag No</th>
                                <th class="pb-2 text-left">Equipment</th>
                                <th class="pb-2 text-left">Vibrasi/Temp</th>
                                <th class="pb-2 text-right">Total Laporan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($topEquipmentRusak as $idx => $item)
                                                        <tr>
                                <td class="py-2 text-xs text-gray-400">{{ $idx + 1 }}</td>
                                <td class="py-2 font-mono text-xs">
                                    <a href="{{ route('assets.show', $item->asset->id) }}" class="text-gray-600 hover:text-[#0E9E8E] transition">{{ $item->asset->tag_no ?? '—' }}</a>
                                </td>
                                <td class="py-2 text-gray-800 text-xs">
                                    <a href="{{ route('assets.show', $item->asset->id) }}" class="hover:text-[#0E9E8E] transition">{{ $item->asset->description ?? '—' }}</a>
                                </td>
                                <td class="py-2">
                                    @php
                                        $c = match($item->asset->status ?? 'normal') {
                                            'danger' => 'bg-red-100 text-red-700',
                                            'alarm' => 'bg-amber-100 text-amber-700',
                                            default => 'bg-green-100 text-green-700',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $c }}">
                                        {{ ucfirst($item->asset->status ?? 'normal') }}
                                    </span>
                                </td>
                                <td class="py-2 text-right font-semibold text-gray-800">{{ $item->total }}x</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- CM Alert Terbaru --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">CM Alert — Vibrasi / Temperature</h2>
                <a href="{{ route('cm.index') }}" class="text-xs text-[#0E9E8E] hover:underline">Lihat semua →</a>
            </div>
            <div class="p-5 space-y-3">
                @forelse($cmAlerts as $alert)
                    <div class="flex items-start gap-3 p-3 rounded-lg bg-red-50 border-l-4 border-red-500">
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold text-gray-800">
                                {{ $alert->asset->tag_no ?? '—' }} — {{ $alert->asset->description ?? '—' }}
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">
                                {{ $alert->kategori }} · {{ ucfirst($alert->severity) }}
                                · {{ $alert->tanggal ? $alert->tanggal->format('d M Y') : '' }}
                            </div>
                            @if($alert->analysis)
                                <p class="text-xs text-gray-600 mt-1">{{ Str::limit($alert->analysis, 120) }}</p>
                            @endif
                        </div>
                        <span class="shrink-0 text-[10px] uppercase font-semibold
                            {{ $alert->severity == 'critical' ? 'text-red-600' : 'text-amber-600' }}">
                            {{ $alert->severity }}
                        </span>
                    </div>
                @empty
                    <p class="text-gray-400 text-sm text-center py-6">Tidak ada alert CM saat ini.</p>
                @endforelse
            </div>
        </div>

    </div>

        {{-- KANAN: 1/3 — Insight KPI --}}
    <div class="space-y-4">

                {{-- Equipment Danger — status vibrasi & temperature dari CM --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Equipment Danger</h2>
                <span class="text-[10px] text-gray-400">Vibrasi & Temperature</span>
            </div>
            <div class="p-4">
                @if($equipmentDanger > 0)
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                        <span class="text-sm font-bold text-red-600">{{ $equipmentDanger }} equipment</span>
                        <span class="text-xs text-gray-400">status danger dari CM</span>
                    </div>
                    <div class="space-y-1.5 max-h-32 overflow-y-auto">
                        @foreach($equipmentDangerList as $eq)
                            <a href="{{ route('assets.show', $eq->id) }}"
                               class="flex items-center gap-2 text-xs bg-red-50 rounded-lg px-3 py-1.5 hover:bg-red-100 transition cursor-pointer">
                                <span class="font-mono text-gray-600">{{ $eq->tag_no }}</span>
                                <span class="text-gray-500 truncate">{{ Str::limit($eq->description, 30) }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-center gap-2 text-sm text-gray-500 py-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Semua vibrasi & temperature dalam batas normal.
                    </div>
                @endif
            </div>
        </div>

        {{-- Sparepart Kritis --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Sparepart Kritis</h2>
                <a href="{{ route('spareparts.index') }}" class="text-xs text-[#0E9E8E] hover:underline">Lihat →</a>
            </div>
            <div class="p-4">
                @if($stokKritis > 0)
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <span class="text-sm font-bold text-amber-600">{{ $stokKritis }} item</span>
                        <span class="text-xs text-gray-400">stok di bawah minimum</span>
                    </div>
                                        <div class="space-y-1.5 max-h-32 overflow-y-auto">
                        @foreach($sparepartKritisList as $sp)
                            <a href="{{ route('spareparts.show', $sp->id) }}"
                               class="flex items-center justify-between text-xs bg-amber-50 rounded-lg px-3 py-1.5 hover:bg-amber-100 transition cursor-pointer">
                                <span class="font-mono text-gray-600">{{ $sp->kode_material ?? '—' }}</span>
                                <span class="text-gray-500">{{ $sp->stok_tersedia }}/{{ $sp->stok_minimum }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-center gap-2 text-sm text-gray-500 py-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Semua stok sparepart aman.
                    </div>
                @endif
            </div>
        </div>

        {{-- Downtime vs KPI --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Downtime vs KPI</h2>
            </div>
            <div class="p-4">
                @php
                    $dStatus = $kpiDowntime['status'];
                    $dColor  = $dStatus == 'danger' ? 'text-red-600' : ($dStatus == 'warning' ? 'text-amber-600' : 'text-green-600');
                    $dBg     = $dStatus == 'danger' ? 'bg-red-50 border-red-200' : ($dStatus == 'warning' ? 'bg-amber-50 border-amber-200' : 'bg-green-50 border-green-200');
                    $dIcon   = $dStatus == 'danger' ? 'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : ($dStatus == 'warning' ? 'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z');
                @endphp
                <div class="p-3 rounded-lg border {{ $dBg }} mb-3">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 shrink-0 mt-0.5 {{ $dColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $dIcon }}"/></svg>
                        <div>
                            <p class="text-xs {{ $dColor }} font-medium">{{ $kpiDowntime['pesan'] }}</p>
                        </div>
                    </div>
                </div>
                {{-- Progress bar --}}
                <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                    @php
                        $dPct = min($kpiDowntime['persen'], 100);
                        $dBarColor = $dStatus == 'danger' ? 'bg-red-500' : ($dStatus == 'warning' ? 'bg-amber-500' : 'bg-green-500');
                    @endphp
                    <div class="h-full rounded-full transition-all {{ $dBarColor }}" style="width: {{ $dPct }}%;"></div>
                </div>
                <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                    <span>0 jam</span>
                    <span>{{ $kpiDowntime['total_jam'] }} jam</span>
                    <span>{{ $kpiDowntime['kpi_jam'] }} jam (KPI)</span>
                </div>
            </div>
        </div>

        {{-- Lembur vs KPI --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Jam Lembur vs KPI</h2>
            </div>
            <div class="p-4">
                @php
                    $lStatus = $kpiLembur['status'];
                    $lColor  = $lStatus == 'danger' ? 'text-red-600' : ($lStatus == 'warning' ? 'text-amber-600' : 'text-green-600');
                    $lBg     = $lStatus == 'danger' ? 'bg-red-50 border-red-200' : ($lStatus == 'warning' ? 'bg-amber-50 border-amber-200' : 'bg-green-50 border-green-200');
                @endphp
                <div class="p-3 rounded-lg border {{ $lBg }} mb-3">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 shrink-0 mt-0.5 {{ $lColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="text-xs {{ $lColor }} font-medium">{{ $kpiLembur['pesan'] }}</p>
                        </div>
                    </div>
                </div>
                {{-- Progress bar --}}
                <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                    @php
                        $lPct = min($kpiLembur['persen'], 100);
                        $lBarColor = $lStatus == 'danger' ? 'bg-red-500' : ($lStatus == 'warning' ? 'bg-amber-500' : 'bg-green-500');
                    @endphp
                    <div class="h-full rounded-full transition-all {{ $lBarColor }}" style="width: {{ $lPct }}%;"></div>
                </div>
                <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                    <span>0 jam</span>
                    <span>{{ $kpiLembur['total_jam'] }} jam</span>
                    <span>{{ $kpiLembur['kpi_jam'] }} jam (KPI)</span>
                </div>
            </div>
        </div>

        <a href="{{ route('dss.index') }}"
           class="block text-center text-xs text-[#0E9E8E] hover:underline py-2 border border-dashed border-gray-200 rounded-lg">
            Buka halaman DSS untuk analisis lengkap →
        </a>

    </div>

</div>

@endsection

@push('scripts')
<script>
    console.log('Dashboard loaded.');
</script>
@endpush

