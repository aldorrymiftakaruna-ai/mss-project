@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-sub', '— Ringkasan & insight lengkap')

@section('content')

{{-- Filter Perusahaan --}}
<div class="flex items-center justify-between mb-5">
    <div class="flex items-center gap-2">
        <span class="text-xs text-gray-500 uppercase tracking-wider">Filter PT:</span>
        <form method="GET" action="{{ route('dashboard') }}" id="filter-form">
            <select name="company_id" onchange="this.form.submit()"
                    class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]/30 focus:border-[#0E9E8E] bg-white">
                <option value="0" {{ $companyId == 0 ? 'selected' : '' }}>Semua Perusahaan</option>
                                @foreach($companies->where('id', '!=', 3) as $c)
                <option value="{{ $c->id }}" {{ $companyId == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="text-[10px] text-gray-400">
        @if($companyId > 0)
            Data khusus <strong>{{ $companies->firstWhere('id', $companyId)->name ?? '' }}</strong>
        @else
            Data gabungan semua perusahaan
        @endif
    </div>
</div>

{{-- KPI Cards --}}
<div class="grid grid-cols-6 gap-4 mb-6">
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Equipment</div>
        <div class="text-3xl font-bold text-gray-900">{{ $totalAssets }}</div>
        <div class="text-xs text-gray-400 mt-1">TERDAFTAR</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Equipment Danger</div>
        <div class="text-3xl font-bold text-red-500">{{ $cmMeas['total_danger'] }}</div>
        <div class="text-xs text-gray-400 mt-1">VIBRASI >= 7.0 MM/S / TEMP >= 85°C</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Laporan Hari Ini</div>
        <div class="text-3xl font-bold text-[#0E9E8E]">{{ $laporanHariIni }}</div>
        <div class="text-xs text-gray-400 mt-1">MASUK</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Downtime Bulan Ini</div>
        <div class="text-2xl font-bold text-orange-600">{{ round($totalDowntimeBulanIni / 60, 1) }} jam</div>
        <div class="text-xs text-gray-400 mt-1">{{ number_format($totalDowntimeBulanIni) }} MENIT</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Lembur Bulan Ini</div>
        <div class="text-2xl font-bold text-purple-600">{{ $totalLemburBulanIni }} jam</div>
        <div class="text-xs text-gray-400 mt-1">{{ $totalLaporanLemburBulanIni }} LAPORAN</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Jam Kerja (Minggu Ini)</div>
        @php $jkJam = ($durasi['total_menit'] ?? 0) > 0 ? round($durasi['total_menit'] / 60, 1) : ($durasi['total_jam'] ?? 0); @endphp
        <div class="text-2xl font-bold text-gray-700">{{ $jkJam }} <span class="text-sm font-normal text-gray-400">jam</span></div>
        <div class="text-xs text-gray-400 mt-1">{{ $durasi['total_laporan'] ?? 0 }} LAPORAN</div>
    </div>
</div>

{{-- Grid Utama --}}
<div class="grid grid-cols-3 gap-5">

    {{-- KIRI: 2/3 --}}
    <div class="col-span-2 space-y-5">

        {{-- Chart Tren Laporan (7/30 Hari + Tahunan) --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Tren Laporan</h2>
                <div class="flex items-center gap-2">
                    <select id="tren-filter"
                            class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]/30 focus:border-[#0E9E8E]">
                        <option value="7hari">7 Hari</option>
                        <option value="30hari">30 Hari</option>
                        <option value="tahunan">Per Bulan ({{ now()->year }})</option>
                    </select>
                </div>
            </div>
            <div class="p-5">
                @php
                    $max7 = max(max($chart7Hari['data'] ?: [0]), 1);
                    $max30 = max(max($chart30Hari['data'] ?: [0]), 1);
                    $maxTahunan = max(max($chartTahunan['data'] ?: [0]), 1);
                @endphp
                <div id="tren-chart-container">
                    <div id="tren-view-7hari" class="flex items-end gap-2 h-28">
                        @foreach($chart7Hari['data'] as $i => $val)
                            @php $height = max(round(($val / $max7) * 100), 3); @endphp
                            <div class="flex-1 flex flex-col items-center gap-1">
                                <span class="text-xs font-semibold text-gray-600">{{ $val }}</span>
                                <div class="w-full rounded-md bg-[#0E9E8E]/20 relative" style="height: 90px;">
                                    <div class="absolute bottom-0 w-full rounded-md bg-[#0E9E8E] transition-all" style="height: {{ $height }}%;"></div>
                                </div>
                                <span class="text-[10px] text-gray-400 mt-1">{{ $chart7Hari['labels'][$i] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div id="tren-view-30hari" class="flex items-end gap-1 h-28 hidden">
                        @foreach($chart30Hari['data'] as $i => $val)
                            @php $height = max(round(($val / $max30) * 100), 3); @endphp
                            <div class="flex-1 flex flex-col items-center gap-1">
                                <span class="text-[10px] font-semibold text-gray-600 tren-val">{{ $val }}</span>
                                <div class="w-full rounded-md bg-[#0E9E8E]/20 relative" style="height: 90px;">
                                    <div class="absolute bottom-0 w-full rounded-md bg-[#0E9E8E] transition-all" style="height: {{ $height }}%;"></div>
                                </div>
                                <span class="text-[8px] text-gray-400 mt-0.5 truncate w-full text-center tren-label">{{ $chart30Hari['labels'][$i] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div id="tren-view-tahunan" class="flex items-end gap-2 h-28 hidden">
                        @foreach($chartTahunan['data'] as $i => $val)
                            @php $height = max(round(($val / $maxTahunan) * 100), 3); @endphp
                            <div class="flex-1 flex flex-col items-center gap-1">
                                <span class="text-[10px] font-semibold text-gray-600 tren-val">{{ $val }}</span>
                                <div class="w-full rounded-md bg-[#0E9E8E]/20 relative" style="height: 90px;">
                                    <div class="absolute bottom-0 w-full rounded-md bg-[#0E9E8E] transition-all" style="height: {{ $height }}%;"></div>
                                </div>
                                <span class="text-[10px] text-gray-400 mt-0.5">{{ $chartTahunan['labels'][$i] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Distribusi Jenis Pekerjaan --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Distribusi Jenis Pekerjaan</h2>
            </div>
            <div class="p-5">
                @if($distribusiJenis->isEmpty())
                    <p class="text-gray-400 text-sm text-center py-6">Belum ada data.</p>
                @else
                    @php $totalJenis = $distribusiJenis->sum('total'); @endphp
                    <div class="space-y-3">
                        @foreach($distribusiJenis as $item)
                            @php $pct = $totalJenis > 0 ? round(($item->total / $totalJenis) * 100, 1) : 0; @endphp
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-700 font-medium capitalize">{{ $item->jenis ?? 'Tidak dikategorikan' }}</span>
                                    <span class="text-gray-500">{{ $item->total }} ({{ $pct }}%)</span>
                                </div>
                                <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full bg-[#0E9E8E] transition-all" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Top 10 Equipment Bermasalah (6 bulan) --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Top 10 Equipment Bermasalah</h2>
                <span class="text-xs text-gray-400">6 bulan terakhir</span>
            </div>
            <div class="p-5">
                @if($topEquipment->isEmpty())
                    <p class="text-gray-400 text-sm text-center py-6">Belum ada data maintenance.</p>
                @else
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-xs text-gray-500 uppercase border-b border-gray-100">
                                <th class="pb-2 text-left">#</th>
                                <th class="pb-2 text-left">Tag No</th>
                                <th class="pb-2 text-left">Equipment</th>
                                <th class="pb-2 text-left">Status</th>
                                <th class="pb-2 text-right">Total Laporan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($topEquipment as $idx => $item)
                            <tr>
                                <td class="py-2 text-xs text-gray-400">{{ $idx + 1 }}</td>
                                <td class="py-2 font-mono text-xs text-gray-600">{{ $item->asset->tag_no ?? '—' }}</td>
                                <td class="py-2 text-gray-800 text-xs">{{ $item->asset->description ?? '—' }}</td>
                                <td class="py-2">
                                    @php
                                        $c = match($item->asset->status ?? 'normal') {
                                            'danger' => 'bg-red-100 text-red-700',
                                            'alarm' => 'bg-amber-100 text-amber-700',
                                            default => 'bg-green-100 text-green-700',
                                        };
                                    @endphp
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $c }}">{{ ucfirst($item->asset->status ?? 'normal') }}</span>
                                </td>
                                <td class="py-2 text-right font-semibold text-gray-800">{{ $item->total }}x</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="mt-3">
                        <a href="{{ route('maintenance.index') }}" class="text-xs text-[#0E9E8E] hover:underline">Lihat semua laporan →</a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Vibrasi & Temperature Terbaru --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Vibrasi & Temperature — Equipment Danger</h2>
                <a href="{{ route('cm.index') }}" class="text-xs text-[#0E9E8E] hover:underline">Lihat semua →</a>
            </div>
            <div class="p-5">
                @php
                    $vibCount = $cmMeas['over_vibrasi']->count();
                    $tempCount = $cmMeas['over_temp']->count();
                    $totalDanger = $cmMeas['total_danger'];
                @endphp

                @if($totalDanger === 0)
                    <div class="flex items-center gap-2 text-sm text-gray-500 py-4">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Semua vibrasi & temperature dalam batas normal dari {{ number_format($cmMeas['total_measurements']) }} data pengukuran.
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="text-center p-3 rounded-lg bg-red-50">
                            <div class="text-xl font-bold text-red-600">{{ $vibCount }}</div>
                            <div class="text-[10px] text-gray-500">Vibrasi >= 7.0 mm/s</div>
                        </div>
                        <div class="text-center p-3 rounded-lg bg-orange-50">
                            <div class="text-xl font-bold text-orange-600">{{ $tempCount }}</div>
                            <div class="text-[10px] text-gray-500">Temp >= 85 °C</div>
                        </div>
                    </div>

                    @if($vibCount > 0)
                    <div class="space-y-1.5 mb-3">
                        <div class="text-xs font-semibold text-gray-700 mb-1">Vibrasi Tinggi ({{ $vibCount }} equipment):</div>
                        <div class="space-y-1 max-h-40 overflow-y-auto">
                            @foreach($cmMeas['over_vibrasi'] as $eq)
                            <a href="{{ route('cm.index') }}"
                               class="flex items-center justify-between text-xs bg-red-50 rounded-lg px-3 py-1.5 hover:bg-red-100 transition cursor-pointer">
                                <span class="font-mono text-gray-600 truncate">{{ $eq->tag_no }}</span>
                                <span class="text-red-600 font-medium shrink-0 ml-2">{{ $eq->nilai_vibrasi }} mm/s</span>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($tempCount > 0)
                    <div class="space-y-1.5">
                        <div class="text-xs font-semibold text-gray-700 mb-1">Temperature Tinggi ({{ $tempCount }} equipment):</div>
                        <div class="space-y-1 max-h-40 overflow-y-auto">
                            @foreach($cmMeas['over_temp'] as $eq)
                            <a href="{{ route('cm.index') }}"
                               class="flex items-center justify-between text-xs bg-orange-50 rounded-lg px-3 py-1.5 hover:bg-orange-100 transition cursor-pointer">
                                <span class="font-mono text-gray-600 truncate">{{ $eq->tag_no }}</span>
                                <span class="text-orange-600 font-medium shrink-0 ml-2">{{ $eq->nilai_temp }} °C</span>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="text-[10px] text-gray-400 mt-3">
                        Data measurement terbaru per {{ now()->format('d M Y') }} ({{ number_format($cmMeas['total_measurements']) }} total pengukuran).
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- KANAN: 1/3 --}}
    <div class="space-y-4">

        {{-- Rekomendasi --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Insight & Rekomendasi</h2>
                <p class="text-xs text-gray-400 mt-0.5">Berdasarkan analisis data terkini</p>
            </div>
            <div class="p-5 space-y-3 max-h-[370px] overflow-y-auto">
                @forelse($rekomendasi as $rek)
                <div class="p-3 rounded-lg border-l-4 {{ $rek['level'] == 'high' ? 'border-red-500 bg-red-50' : ($rek['level'] == 'med' ? 'border-amber-500 bg-amber-50' : 'border-green-500 bg-green-50') }}">
                    <div class="flex items-start gap-2">
                        @if($rek['level'] == 'high')
                        <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @elseif($rek['level'] == 'med')
                        <svg class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                        <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-semibold text-gray-800">{{ $rek['title'] }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ $rek['desc'] }}</div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <svg class="w-10 h-10 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    <p class="text-gray-400 text-sm">Tidak ada rekomendasi saat ini.</p>
                    <p class="text-xs text-gray-300 mt-1">Semua indikator dalam batas normal.</p>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Produktivitas Karyawan --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-gray-800 uppercase tracking-wider">Produktivitas Karyawan</h3>
                <span class="text-[10px] text-gray-400">Top 5</span>
            </div>
            <div class="text-[11px] text-gray-600">
                @if($produktivitas->isEmpty())
                    <p class="text-gray-400 text-center py-6 text-sm">Belum ada data produktivitas. Pastikan teknisi melakukan input work_duration_minutes pada laporan.</p>
                @else
                    @foreach($produktivitas->take(5) as $idx => $item)
                    <div class="flex items-center justify-between py-1.5 {{ $idx > 0 ? 'border-t border-gray-50' : '' }}">
                        <span class="text-gray-700 truncate">{{ $item->reporter->name ?? 'Teknisi #'.$item->reported_by }}</span>
                        <span class="text-gray-500 font-medium shrink-0 ml-2">{{ round($item->total_menit / 60, 1) }} jam ({{ $item->total_laporan }} laporan)</span>
                    </div>
                    @endforeach
                    @if($produktivitas->count() > 5)
                    <p class="text-[10px] text-gray-400 mt-2">+ {{ $produktivitas->count() - 5 }} lainnya</p>
                    @endif
                @endif
            </div>
        </div>

        {{-- Statistik CM Finding --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">CM Finding — Semua Temuan</h2>
            </div>
            <div class="p-5">
                @if($severityCm['total'] === 0)
                    <p class="text-gray-400 text-sm text-center py-6">Belum ada data CM Finding.</p>
                @else
                    <div class="grid grid-cols-3 gap-2 mb-4">
                        <div class="text-center p-2 rounded-lg bg-red-50">
                            <div class="text-base font-bold text-red-600">{{ $severityCm['severities']['critical'] }}</div>
                            <div class="text-[9px] text-gray-500 uppercase">Critical</div>
                        </div>
                        <div class="text-center p-2 rounded-lg bg-orange-50">
                            <div class="text-base font-bold text-orange-600">{{ $severityCm['severities']['high'] }}</div>
                            <div class="text-[9px] text-gray-500 uppercase">High</div>
                        </div>
                        <div class="text-center p-2 rounded-lg bg-amber-50">
                            <div class="text-base font-bold text-amber-600">{{ $severityCm['severities']['medium'] }}</div>
                            <div class="text-[9px] text-gray-500 uppercase">Medium</div>
                        </div>
                        <div class="text-center p-2 rounded-lg bg-green-50">
                            <div class="text-base font-bold text-green-600">{{ $severityCm['severities']['low'] }}</div>
                            <div class="text-[9px] text-gray-500 uppercase">Low</div>
                        </div>
                        <div class="text-center p-2 rounded-lg bg-gray-50">
                            <div class="text-base font-bold text-gray-600">{{ $severityCm['severities']['unclassified'] }}</div>
                            <div class="text-[9px] text-gray-500 uppercase">Belum Diklasifikasi</div>
                        </div>
                        <div class="text-center p-2 rounded-lg bg-blue-50">
                            <div class="text-base font-bold text-blue-600">{{ $severityCm['totalOpen'] }}</div>
                            <div class="text-[9px] text-gray-500 uppercase">Total Open</div>
                        </div>
                    </div>
                    <div class="text-xs text-gray-600">
                        <span>Total temuan: <strong>{{ $severityCm['total'] }}</strong></span>
                        @if($severityCm['openCritical'] > 0)
                            <div class="text-red-600">{{ $severityCm['openCritical'] }} critical masih open</div>
                        @endif
                        @if($severityCm['openHigh'] > 0)
                            <div class="text-orange-600">{{ $severityCm['openHigh'] }} high masih open</div>
                        @endif
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
                        @foreach($stokKritisList as $sp)
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
                    $dStatus = $kpiDt['status'];
                    $dColor  = $dStatus == 'danger' ? 'text-red-600' : ($dStatus == 'warning' ? 'text-amber-600' : 'text-green-600');
                    $dBg     = $dStatus == 'danger' ? 'bg-red-50 border-red-200' : ($dStatus == 'warning' ? 'bg-amber-50 border-amber-200' : 'bg-green-50 border-green-200');
                    $dIcon   = $dStatus == 'danger' ? 'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : ($dStatus == 'warning' ? 'M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z');
                @endphp
                <div class="p-3 rounded-lg border {{ $dBg }} mb-3">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 shrink-0 mt-0.5 {{ $dColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $dIcon }}"/></svg>
                        <div>
                            <p class="text-xs {{ $dColor }} font-medium">{{ $kpiDt['pesan'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                    @php
                        $dPct = min($kpiDt['persen'], 100);
                        $dBarColor = $dStatus == 'danger' ? 'bg-red-500' : ($dStatus == 'warning' ? 'bg-amber-500' : 'bg-green-500');
                    @endphp
                    <div class="h-full rounded-full transition-all {{ $dBarColor }}" style="width: {{ $dPct }}%;"></div>
                </div>
                <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                    <span>0 jam</span>
                    <span>{{ $kpiDt['total_jam'] }} jam</span>
                    <span>{{ $kpiDt['kpi_jam'] }} jam (KPI)</span>
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
                    $lStatus = $kpiOt['status'];
                    $lColor  = $lStatus == 'danger' ? 'text-red-600' : ($lStatus == 'warning' ? 'text-amber-600' : 'text-green-600');
                    $lBg     = $lStatus == 'danger' ? 'bg-red-50 border-red-200' : ($lStatus == 'warning' ? 'bg-amber-50 border-amber-200' : 'bg-green-50 border-green-200');
                @endphp
                <div class="p-3 rounded-lg border {{ $lBg }} mb-3">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 shrink-0 mt-0.5 {{ $lColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div>
                            <p class="text-xs {{ $lColor }} font-medium">{{ $kpiOt['pesan'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                    @php
                        $lPct = min($kpiOt['persen'], 100);
                        $lBarColor = $lStatus == 'danger' ? 'bg-red-500' : ($lStatus == 'warning' ? 'bg-amber-500' : 'bg-green-500');
                    @endphp
                    <div class="h-full rounded-full transition-all {{ $lBarColor }}" style="width: {{ $lPct }}%;"></div>
                </div>
                <div class="flex justify-between text-[10px] text-gray-400 mt-1">
                    <span>0 jam</span>
                    <span>{{ $kpiOt['total_jam'] }} jam</span>
                    <span>{{ $kpiOt['kpi_jam'] }} jam (KPI)</span>
                </div>
            </div>
        </div>

        <a href="{{ route('dss.integrated') }}"
           class="block text-center text-xs text-[#0E9E8E] hover:underline py-2 border border-dashed border-gray-200 rounded-lg">
            Buka DSS Terintegrasi untuk analisis waterfall lengkap →
        </a>

    </div>

</div>

@endsection

@push('scripts')
<script>
    (function() {
        var filter = document.getElementById('tren-filter');
        if (!filter) return;

        var view7 = document.getElementById('tren-view-7hari');
        var view30 = document.getElementById('tren-view-30hari');
        var viewTahunan = document.getElementById('tren-view-tahunan');

        filter.addEventListener('change', function() {
            var val = this.value;
            view7.classList.toggle('hidden', val !== '7hari');
            view30.classList.toggle('hidden', val !== '30hari');
            viewTahunan.classList.toggle('hidden', val !== 'tahunan');
        });
    })();
</script>
@endpush