@extends('layouts.app')

@section('title', 'Decision Support System')
@section('page-title', 'Decision Support System')
@section('page-sub', '— Analisis & saran sistem')

@section('content')

{{-- ============================== BARIS 1 : KPI ============================== --}}
@php
    $downtimeNpa = $downtimeStats->get(1);
    $downtimeCes = $downtimeStats->get(2);
    $totalDowntimeNpa = $downtimeNpa ? $downtimeNpa->total_downtime : 0;
    $totalDowntimeCes = $downtimeCes ? $downtimeCes->total_downtime : 0;
@endphp
<div class="grid grid-cols-6 gap-4 mb-6">
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Equipment</div>
        <div class="text-3xl font-bold text-gray-900">{{ $totalAssets }}</div>
        <div class="text-xs text-gray-400 mt-1">TERDAFTAR</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Danger</div>
        <div class="text-3xl font-bold text-red-500">{{ $equipmentDanger }}</div>
        <div class="text-xs text-gray-400 mt-1">BUTUH TINDAKAN</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Downtime NPA</div>
        <div class="text-2xl font-bold text-orange-600">{{ round($totalDowntimeNpa / 60, 1) }} jam</div>
        <div class="text-xs text-gray-400 mt-1">{{ $totalDowntimeNpa }} MENIT</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Downtime CES</div>
        <div class="text-2xl font-bold text-orange-600">{{ round($totalDowntimeCes / 60, 1) }} jam</div>
        <div class="text-xs text-gray-400 mt-1">{{ $totalDowntimeCes }} MENIT</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Lembur Bulan Ini</div>
        <div class="text-2xl font-bold text-purple-600">{{ $overtimeStats['total_jam'] }} jam</div>
        <div class="text-xs text-gray-400 mt-1">{{ $overtimeStats['total_laporan'] }} LAPORAN</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Stok Kritis</div>
        <div class="text-3xl font-bold text-amber-500">{{ $stokKritis }}</div>
        <div class="text-xs text-gray-400 mt-1">PERLU REORDER</div>
    </div>
</div>

{{-- ============================== BARIS 2 : Tren + Distribusi | Rekomendasi ============================== --}}
<div class="grid grid-cols-3 gap-5 mb-5">
    {{-- KIRI (2/3) --}}
    <div class="col-span-2 space-y-5">

        {{-- 1. Tren Laporan (30 Hari / Per Bulan) — dengan filter --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Tren Laporan</h2>
                <div class="flex items-center gap-2">
                    <select id="tren-filter"
                            class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]/30 focus:border-[#0E9E8E]">
                        <option value="30hari">30 Hari</option>
                        <option value="tahunan">Per Bulan ({{ now()->year }})</option>
                    </select>
                </div>
            </div>
            <div class="p-5">
                @php
                    $maxTren = max(max($trenBulanan['data'] ?: [0]), 1);
                    $maxTahunan = max(max($chartTahunan['data'] ?: [0]), 1);
                @endphp
                <div id="tren-chart-container">
                    {{-- Default: 30 hari --}}
                    <div id="tren-view-30hari" class="flex items-end gap-1 h-28">
                        @foreach($trenBulanan['data'] as $i => $val)
                            @php $height = max(round(($val / $maxTren) * 100), 3); @endphp
                            <div class="flex-1 flex flex-col items-center gap-1">
                                <span class="text-[10px] font-semibold text-gray-600 tren-val">{{ $val }}</span>
                                <div class="w-full rounded-md bg-[#0E9E8E]/20 relative" style="height: 90px;">
                                    <div class="absolute bottom-0 w-full rounded-md bg-[#0E9E8E] transition-all" style="height: {{ $height }}%;"></div>
                                </div>
                                <span class="text-[8px] text-gray-400 mt-0.5 truncate w-full text-center tren-label">{{ $trenBulanan['labels'][$i] }}</span>
                            </div>
                    @endforeach
                    </div>
                    {{-- Tampilan tahunan (hidden default) --}}
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

        {{-- 2. Distribusi Jenis Pekerjaan --}}
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

        {{-- 3. Tren Downtime per Company (card kecil) --}}
        <div class="bg-white rounded-xl border border-gray-200" style="min-height: 220px;">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Tren Downtime per Company</h2>
                <span class="text-xs text-gray-400">12 bulan</span>
            </div>
            <div class="p-5">
                @php
                    $maxDowntime = max(max($chartDowntime['npa'] ?: [0]), max($chartDowntime['ces'] ?: [0]), 1);
                @endphp
                <div class="flex items-end gap-1.5 h-24">
                    @foreach($chartDowntime['labels'] as $i => $label)
                        @php
                            $npaH = max(round(($chartDowntime['npa'][$i] / $maxDowntime) * 100), 1);
                            $cesH = max(round(($chartDowntime['ces'][$i] / $maxDowntime) * 100), 1);
                        @endphp
                        <div class="flex-1 flex flex-col items-center gap-0.5">
                            <div class="w-full flex items-end gap-0.5 relative" style="height: 70px;">
                                <div class="flex-1 rounded-t-md bg-orange-400 transition-all" style="height: {{ $npaH }}%;" title="NPA: {{ $chartDowntime['npa'][$i] }} mnt"></div>
                                <div class="flex-1 rounded-t-md bg-amber-600 transition-all" style="height: {{ $cesH }}%;" title="CES: {{ $chartDowntime['ces'][$i] }} mnt"></div>
                            </div>
                            <span class="text-[7px] text-gray-400 truncate w-full text-center">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex items-center gap-3 mt-2 text-[10px] text-gray-500">
                    <div class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-orange-400"></span> NPA</div>
                    <div class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-amber-600"></span> CES</div>
                </div>
            </div>
        </div>

    </div>

    {{-- KANAN (1/3) --}}
    <div class="space-y-5">

        {{-- Rekomendasi DSS --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Rekomendasi</h2>
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
        <div class="bg-white rounded-xl border border-gray-200 p-5" style="min-height: 180px;">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-semibold text-gray-800 uppercase tracking-wider">Produktivitas Karyawan</h3>
                <span class="text-[10px] text-gray-400">Top 5</span>
            </div>
            <div class="text-[11px] text-gray-600">
                @if($produktivitasKaryawan->isEmpty())
                    <p class="text-gray-400 text-center py-2">Belum ada data.</p>
                @else
                    @foreach($produktivitasKaryawan->take(5) as $idx => $item)
                    <div class="flex items-center justify-between py-1.5 {{ $idx > 0 ? 'border-t border-gray-50' : '' }}">
                        <span class="text-gray-700 truncate">{{ $item->reporter->name ?? '—' }}</span>
                        <span class="text-gray-500 font-medium shrink-0 ml-2">{{ round($item->total_menit / 60, 1) }} jam</span>
                    </div>
                    @endforeach
                    @if($produktivitasKaryawan->count() > 5)
                    <p class="text-[10px] text-gray-400 mt-2">+ {{ $produktivitasKaryawan->count() - 5 }} lainnya</p>
                    @endif
                @endif
            </div>
        </div>

    </div>
</div>

{{-- ============================== BARIS 3 : Top Equipment + Produktivitas | CM Stats ============================== --}}
<div class="grid grid-cols-3 gap-5 mb-5">
    {{-- KIRI (2/3) --}}
    <div class="col-span-2 space-y-5">

        {{-- 4. Top 10 Equipment Bermasalah (6 bulan) --}}
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
                                <th class="pb-2 text-left">Vibrasi/Temp</th>
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
                @endif
            </div>
        </div>

    </div>

    {{-- KANAN (1/3) --}}
    <div class="space-y-5">

        {{-- Statistik CM Finding --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-semibold text-gray-800">Statistik CM Finding</h2>
            </div>
            <div class="p-5">
                @if($severityCm['total'] === 0)
                    <p class="text-gray-400 text-sm text-center py-6">Belum ada data CM Finding.</p>
                @else
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div class="text-center p-3 rounded-lg bg-red-50">
                            <div class="text-lg font-bold text-red-600">{{ $severityCm['severities']['critical'] }}</div>
                            <div class="text-[10px] text-gray-500 uppercase">Critical</div>
                        </div>
                        <div class="text-center p-3 rounded-lg bg-orange-50">
                            <div class="text-lg font-bold text-orange-600">{{ $severityCm['severities']['high'] }}</div>
                            <div class="text-[10px] text-gray-500 uppercase">High</div>
                        </div>
                        <div class="text-center p-3 rounded-lg bg-amber-50">
                            <div class="text-lg font-bold text-amber-600">{{ $severityCm['severities']['medium'] }}</div>
                            <div class="text-[10px] text-gray-500 uppercase">Medium</div>
                        </div>
                        <div class="text-center p-3 rounded-lg bg-green-50">
                            <div class="text-lg font-bold text-green-600">{{ $severityCm['severities']['low'] }}</div>
                            <div class="text-[10px] text-gray-500 uppercase">Low</div>
                        </div>
                    </div>
                    <div class="space-y-1 text-xs text-gray-600">
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
    </div>
</div>

{{-- ============================== BARIS 4 : Tren Severity CM per Equipment (Full) ============================== --}}
<div class="mb-5">
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Tren Severity CM per Equipment</h2>
        </div>
        <div class="p-5">
            @if($daftarEquipment->isEmpty())
                <p class="text-gray-400 text-sm text-center py-6">Belum ada equipment dengan data CM Finding.</p>
            @else
                <form method="GET" action="{{ route('dss.index') }}" class="mb-5 max-w-md">
                    <label for="asset_id" class="block text-xs text-gray-500 mb-1.5">Pilih Equipment</label>
                    <select name="asset_id" id="asset_id" onchange="this.form.submit()"
                            class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]/30 focus:border-[#0E9E8E]">
                        @foreach($daftarEquipment as $asset)
                            <option value="{{ $asset->id }}" {{ (int) $selectedAssetId === $asset->id ? 'selected' : '' }}>
                                {{ $asset->tag_no }} — {{ $asset->description }} {{ $asset->model ? '(' . $asset->model . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>

                @if($cmTimeline->isEmpty())
                    <p class="text-gray-400 text-sm text-center py-6">Tidak ada data CM Finding untuk equipment ini.</p>
                @else
                    @if($selectedAsset)
                    <div class="mb-4 flex items-center gap-3 text-xs">
                        <span class="text-gray-500">{{ $selectedAsset->tag_no }} — {{ $selectedAsset->description }}</span>
                        @php
                            $c = match($selectedAsset->status) {
                                'danger' => 'bg-red-100 text-red-700',
                                'alarm' => 'bg-amber-100 text-amber-700',
                                default => 'bg-green-100 text-green-700',
                            };
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $c }}">{{ ucfirst($selectedAsset->status) }}</span>
                        <span class="text-gray-400">{{ $cmTimeline->count() }} temuan</span>
                    </div>
                    @endif

                    <div class="relative">
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                        <div class="space-y-4 pl-10">
                            @foreach($cmTimeline as $finding)
                                @php
                                    $sevColor = match($finding->severity) {
                                        'critical' => 'bg-red-500',
                                        'high' => 'bg-orange-500',
                                        'medium' => 'bg-amber-500',
                                        default => 'bg-green-500',
                                    };
                                    $sevLabel = match($finding->severity) {
                                        'critical' => 'text-red-600',
                                        'high' => 'text-orange-600',
                                        'medium' => 'text-amber-600',
                                        default => 'text-green-600',
                                    };
                                    $statusBadge = match($finding->status) {
                                        'closed' => 'bg-green-100 text-green-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    };
                                @endphp
                                <div class="relative">
                                    <div class="absolute -left-[34px] top-1 w-3 h-3 rounded-full ring-4 ring-white {{ $sevColor }}"></div>
                                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <span class="text-xs font-semibold {{ $sevLabel }}">{{ ucfirst($finding->severity) }}</span>
                                                @if($finding->kategori)
                                                    <span class="text-xs text-gray-500 ml-2">{{ $finding->kategori }}</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-medium {{ $statusBadge }}">{{ $finding->status }}</span>
                                                <span class="text-[10px] text-gray-400">{{ $finding->tanggal ? $finding->tanggal->format('d/m/Y') : '—' }}</span>
                                            </div>
                                        </div>
                                        @if($finding->analysis)
                                            <p class="text-xs text-gray-600 mt-1.5">{{ Str::limit($finding->analysis, 150) }}</p>
                                        @endif
                                        @if($finding->finding_code)
                                            <span class="text-[10px] text-gray-400 mt-1 block">Kode: {{ $finding->finding_code }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    (function() {
        var filter = document.getElementById('tren-filter');
        if (!filter) return;

        var view30 = document.getElementById('tren-view-30hari');
        var viewTahunan = document.getElementById('tren-view-tahunan');

        filter.addEventListener('change', function() {
            var val = this.value;
            if (val === '30hari') {
                view30.classList.remove('hidden');
                viewTahunan.classList.add('hidden');
            } else {
                view30.classList.add('hidden');
                viewTahunan.classList.remove('hidden');
            }
        });
    })();
</script>
@endpush
