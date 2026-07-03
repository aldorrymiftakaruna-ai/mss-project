@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-sub', '— Ringkasan hari ini')

@section('content')

{{-- KPI Cards --}}
<div class="grid grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Equipment</div>
        <div class="text-3xl font-bold text-gray-900">{{ $totalAssets }}</div>
        <div class="text-xs text-gray-400 mt-1">TERDAFTAR</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Equipment Danger</div>
        <div class="text-3xl font-bold text-red-500">{{ $equipmentDanger }}</div>
        <div class="text-xs text-gray-400 mt-1">BUTUH TINDAKAN SEGERA</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Laporan Hari Ini</div>
        <div class="text-3xl font-bold text-[#0E9E8E]">{{ $laporanHariIni }}</div>
        <div class="text-xs text-gray-400 mt-1">MASUK</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Stok Sparepart Kritis</div>
        <div class="text-3xl font-bold text-amber-500">{{ $stokKritis }}</div>
        <div class="text-xs text-gray-400 mt-1">PERLU REORDER</div>
    </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Jam Kerja</div>
        <div class="text-3xl font-bold text-blue-500">{{ $totalJamMingguIni }} <span class="text-lg font-normal text-gray-400">jam</span></div>
        <div class="text-xs text-gray-400 mt-1">{{ $totalLaporanMinggu }} LAPORAN · {{ $totalKaryawanAktif }} KARYAWAN</div>
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
                                <th class="pb-2 text-left">Status</th>
                                <th class="pb-2 text-right">Total Laporan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($topEquipmentRusak as $idx => $item)
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

    {{-- KANAN: 1/3 — DSS Rekomendasi --}}
    <div class="bg-white rounded-xl border border-gray-200 h-fit">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Rekomendasi DSS</h2>
            <a href="{{ route('dss.index') }}" class="text-xs text-[#0E9E8E] hover:underline">Detail →</a>
        </div>
        <div class="p-5 space-y-3">
            @forelse($rekomendasi as $rek)
            <div class="p-3 rounded-lg border-l-4 {{ $rek['level'] == 'high' ? 'border-red-500 bg-red-50' : ($rek['level'] == 'med' ? 'border-amber-500 bg-amber-50' : 'border-green-500 bg-green-50') }}">
                <div class="text-xs font-semibold text-gray-800">{{ $rek['title'] }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ $rek['desc'] }}</div>
            </div>
            @empty
            <p class="text-gray-400 text-sm text-center py-8">Tidak ada rekomendasi saat ini.</p>
            @endforelse
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    console.log('Dashboard loaded.');
</script>
@endpush

