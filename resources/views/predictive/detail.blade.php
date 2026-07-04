@extends('layouts.app')

@section('title', 'Detail Risk - ' . $asset->tag_no)
@section('page-title', 'Detail Risk: ' . $asset->tag_no)
@section('page-sub', $asset->description)

@section('content')
<div class="mb-5 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <span class="text-sm text-gray-500">{{ $asset->tag_no }}</span>
        <span class="text-xs text-gray-400">|</span>
        @if($riskScore)
            @php
                $badgeColor = match($riskScore->category) {
                    'tinggi' => 'bg-red-100 text-red-700',
                    'sedang' => 'bg-amber-100 text-amber-700',
                    default  => 'bg-green-100 text-green-700',
                };
            @endphp
            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">Risiko {{ ucfirst($riskScore->category) }}</span>
        @else
            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Belum Dinilai</span>
        @endif
    </div>
    <div class="flex items-center gap-2">
        @if($riskScore)
        <button id="btnRecalcOne" data-asset-id="{{ $asset->id }}" class="text-xs px-3 py-1.5 rounded-lg bg-[#0E9E8E]/10 text-[#0E9E8E] hover:bg-[#0E9E8E]/20 transition font-medium">
            Hitung Ulang
        </button>
        @endif
        <a href="{{ route('predictive.index') }}" class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition">&larr; Kembali</a>
    </div>
</div>

{{-- INFO EQUIPMENT --}}
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Status</div>
        @php
            $statusColor = match($asset->status) {
                'danger' => 'bg-red-100 text-red-700',
                'alarm' => 'bg-amber-100 text-amber-700',
                default => 'bg-green-100 text-green-700',
            };
        @endphp
        <div class="text-lg font-bold {{ str_contains($statusColor, 'red') ? 'text-red-600' : (str_contains($statusColor, 'amber') ? 'text-amber-600' : 'text-green-600') }}">
            {{ ucfirst($asset->status) }}
        </div>
        <div class="text-xs text-gray-400 mt-1">STATUS TERKINI</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Motor KW</div>
        <div class="text-lg font-bold text-gray-900">{{ $asset->motor_kw ?? '—' }}</div>
        <div class="text-xs text-gray-400 mt-1">KAPASITAS</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Threshold Alarm</div>
        <div class="text-lg font-bold text-amber-600">{{ $thresholds['alarm'] ?? '—' }} mm/s</div>
        <div class="text-xs text-gray-400 mt-1">VIBRASI</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Threshold Danger</div>
        <div class="text-lg font-bold text-red-600">{{ $thresholds['danger'] ?? '—' }} mm/s</div>
        <div class="text-xs text-gray-400 mt-1">VIBRASI / {{ $thresholds['tempDanger'] ?? '—' }} C TEMP</div>
    </div>
</div>

{{-- SKOR RISIKO & PARAMETER --}}
@if($riskScore)
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
    {{-- Skor --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Skor Risiko</h3>
        <div class="flex items-center gap-4">
            <div class="relative w-24 h-24">
                <svg class="w-24 h-24 -rotate-90" viewBox="0 0 36 36">
                    <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                    <circle cx="18" cy="18" r="15.5" fill="none" 
                        stroke="{{ $riskScore->category == 'tinggi' ? '#ef4444' : ($riskScore->category == 'sedang' ? '#f59e0b' : '#10b981') }}"
                        stroke-width="3" stroke-dasharray="{{ $riskScore->score * 100 }}, 100"
                        stroke-linecap="round"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-xl font-bold text-gray-800">{{ number_format($riskScore->score, 3) }}</span>
                </div>
            </div>
            <div>
                <span class="text-sm font-semibold">Kategori: {{ ucfirst($riskScore->category) }}</span>
                <p class="text-xs text-gray-500 mt-1">Terakhir dihitung: {{ $riskScore->calculated_at ? $riskScore->calculated_at->format('d/m/Y H:i') : '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Parameters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Parameter</h3>
        @php $params = $riskScore->parameters_json ?? []; @endphp
        <div class="grid grid-cols-2 gap-3">
            <div class="p-3 rounded-lg bg-gray-50">
                <div class="text-xs text-gray-500">Slope Vibrasi</div>
                <div class="text-sm font-semibold text-gray-800">{{ number_format($params['vibration_slope'] ?? 0, 4) }}</div>
                <div class="text-[10px] text-gray-400">mm/s per hari</div>
            </div>
            <div class="p-3 rounded-lg bg-gray-50">
                <div class="text-xs text-gray-500">Slope Temperatur</div>
                <div class="text-sm font-semibold text-gray-800">{{ number_format($params['temperature_slope'] ?? 0, 4) }}</div>
                <div class="text-[10px] text-gray-400">C per hari</div>
            </div>
            <div class="p-3 rounded-lg bg-gray-50">
                <div class="text-xs text-gray-500">Level Vibrasi</div>
                <div class="text-sm font-semibold text-gray-800">{{ number_format(($params['vibration_level_norm'] ?? 0) * 100, 1) }}%</div>
                <div class="text-[10px] text-gray-400">dari threshold alarm</div>
            </div>
            <div class="p-3 rounded-lg bg-gray-50">
                <div class="text-xs text-gray-500">Level Temperatur</div>
                <div class="text-sm font-semibold text-gray-800">{{ number_format(($params['temperature_level_norm'] ?? 0) * 100, 1) }}%</div>
                <div class="text-[10px] text-gray-400">dari threshold danger</div>
            </div>
            <div class="p-3 rounded-lg bg-gray-50">
                <div class="text-xs text-gray-500">Rate of Change</div>
                <div class="text-sm font-semibold text-gray-800">{{ number_format($params['rate_of_change'] ?? 0, 4) }}</div>
                <div class="text-[10px] text-gray-400">percepatan tren</div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- GRAFIK TREN --}}
<div class="bg-white rounded-xl border border-gray-200 mb-6">
    <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Tren Vibrasi (Vertical)</h2>
        <p class="text-xs text-gray-400 mt-0.5">20 pengukuran terakhir</p>
    </div>
    <div class="p-5">
        @if(empty($trend['labels']))
            <p class="text-gray-400 text-sm text-center py-6">Belum ada data CM untuk equipment ini.</p>
        @else
            @php
                $maxVib = max(
                    max($trend['driverDeVibV'] ?: [0]),
                    max($trend['driverNdeVibV'] ?: [0]),
                    max($trend['drivenDeVibV'] ?: [0]),
                    max($trend['drivenNdeVibV'] ?: [0]),
                    1
                );
            @endphp
            <div class="relative h-52">
                {{-- Threshold lines --}}
                <div class="absolute inset-0">
                    <div class="border-t border-dashed border-red-300 text-[10px] text-red-400 text-right pr-2" style="height: {{ ($thresholds['danger'] / $maxVib) * 100 > 100 ? 100 : ($thresholds['danger'] / $maxVib) * 100 }}%;">
                        @if($thresholds['danger'] / $maxVib <= 1)
                            Danger {{ $thresholds['danger'] }}
                        @endif
                    </div>
                    <div class="border-t border-dashed border-amber-300 text-[10px] text-amber-400 text-right pr-2" style="height: {{ ($thresholds['alarm'] / $maxVib) * 100 > 100 ? 100 : ($thresholds['alarm'] / $maxVib) * 100 }}%;">
                        @if($thresholds['alarm'] / $maxVib <= 1)
                            Alarm {{ $thresholds['alarm'] }}
                        @endif
                    </div>
                </div>
                {{-- Chart bars --}}
                <div class="flex items-end gap-1 h-full relative z-10">
                    @foreach($trend['labels'] as $i => $label)
                        @php
                            $dd = (float)($trend['driverDeVibV'][$i] ?? 0);
                            $dn = (float)($trend['driverNdeVibV'][$i] ?? 0);
                            $nd = (float)($trend['drivenDeVibV'][$i] ?? 0);
                            $nn = (float)($trend['drivenNdeVibV'][$i] ?? 0);
                            $h_dd = ($dd / $maxVib) * 180;
                            $h_dn = ($dn / $maxVib) * 180;
                            $h_nd = ($nd / $maxVib) * 180;
                            $h_nn = ($nn / $maxVib) * 180;
                        @endphp
                        <div class="flex-1 flex flex-col items-center gap-0.5">
                            <div class="w-full flex items-end gap-0.5" style="height: 180px;">
                                <div class="flex-1 rounded-t-sm bg-[#0E9E8E] transition-all" style="height: {{ $h_dd }}px;" title="DE Vib V: {{ $dd }}"></div>
                                <div class="flex-1 rounded-t-sm bg-teal-400 transition-all" style="height: {{ $h_dn }}px;" title="NDE Vib V: {{ $dn }}"></div>
                                <div class="flex-1 rounded-t-sm bg-cyan-500 transition-all" style="height: {{ $h_nd }}px;" title="Driven DE Vib V: {{ $nd }}"></div>
                                <div class="flex-1 rounded-t-sm bg-cyan-300 transition-all" style="height: {{ $h_nn }}px;" title="Driven NDE Vib V: {{ $nn }}"></div>
                            </div>
                            <span class="text-[7px] text-gray-400 truncate w-full text-center">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="flex items-center gap-3 mt-2 text-[10px] text-gray-500">
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-[#0E9E8E]"></span> Driver DE</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-teal-400"></span> Driver NDE</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-cyan-500"></span> Driven DE</span>
                <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-cyan-300"></span> Driven NDE</span>
            </div>
        @endif
    </div>
</div>

{{-- TREN TEMPERATUR --}}
<div class="bg-white rounded-xl border border-gray-200 mb-6">
    <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Tren Temperatur</h2>
    </div>
    <div class="p-5">
        @if(empty($trend['labels']))
            <p class="text-gray-400 text-sm text-center py-6">Belum ada data.</p>
        @else
            @php
                $maxTemp = max(
                    max($trend['driverDeTemp'] ?: [0]),
                    max($trend['drivenDeTemp'] ?: [0]),
                    1
                );
            @endphp
            <div class="relative h-36">
                <div class="border-t border-dashed border-red-300 text-[10px] text-red-400 text-right pr-2" style="height: {{ $thresholds['tempDanger'] > 0 && $thresholds['tempDanger'] / $maxTemp <= 1 ? ($thresholds['tempDanger'] / $maxTemp) * 100 : 0 }}%;">
                    Danger {{ $thresholds['tempDanger'] }}C
                </div>
                <div class="flex items-end gap-1.5 h-28 relative">
                    @foreach($trend['labels'] as $i => $label)
                        @php
                            $dt = (float)($trend['driverDeTemp'][$i] ?? 0);
                            $nt = (float)($trend['drivenDeTemp'][$i] ?? 0);
                            $h_dt = ($dt / $maxTemp) * 100;
                            $h_nt = ($nt / $maxTemp) * 100;
                        @endphp
                        <div class="flex-1 flex flex-col items-center gap-0.5">
                            <div class="w-full flex items-end gap-0.5" style="height: 100px;">
                                <div class="flex-1 rounded-t-sm bg-orange-500 transition-all" style="height: {{ $h_dt }}px;" title="Driver DE Temp: {{ $dt }}"></div>
                                <div class="flex-1 rounded-t-sm bg-amber-400 transition-all" style="height: {{ $h_nt }}px;" title="Driven DE Temp: {{ $nt }}"></div>
                            </div>
                            <span class="text-[7px] text-gray-400 truncate w-full text-center">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex items-center gap-3 mt-2 text-[10px] text-gray-500">
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-orange-500"></span> Driver DE</span>
                    <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-amber-400"></span> Driven DE</span>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- CM FINDINGS --}}
<div class="bg-white rounded-xl border border-gray-200">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">Riwayat Temuan CM</h2>
        <span class="text-xs text-gray-400">{{ $recentFindings->count() }} temuan</span>
    </div>
    @if($recentFindings->isEmpty())
        <div class="p-5 text-center">
            <p class="text-xs text-gray-400">Tidak ada temuan CM untuk equipment ini.</p>
        </div>
    @else
        <div class="divide-y divide-gray-50">
            @foreach($recentFindings as $finding)
                @php
                    $sevColor = match($finding->severity) {
                        'critical' => 'bg-red-100 text-red-700',
                        'high' => 'bg-orange-100 text-orange-700',
                        'medium' => 'bg-amber-100 text-amber-700',
                        default => 'bg-green-100 text-green-700',
                    };
                    $statBadge = $finding->status == 'closed' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700';
                @endphp
                <div class="px-5 py-3 flex items-start justify-between gap-3 hover:bg-gray-50/50">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            @if($finding->finding_code)
                                <span class="text-[10px] font-mono text-gray-400">{{ $finding->finding_code }}</span>
                            @endif
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium {{ $sevColor }}">{{ ucfirst($finding->severity) }}</span>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium {{ $statBadge }}">{{ $finding->status }}</span>
                        </div>
                        @if($finding->analysis)
                            <p class="text-xs text-gray-600">{{ Str::limit($finding->analysis, 200) }}</p>
                        @endif
                        @if($finding->kategori)
                            <p class="text-[10px] text-gray-400 mt-0.5">{{ $finding->kategori }}</p>
                        @endif
                    </div>
                    <div class="text-[10px] text-gray-400 shrink-0">
                        {{ $finding->tanggal ? $finding->tanggal->format('d/m/Y') : '—' }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function() {
    var btn = document.getElementById('btnRecalcOne');
    if (!btn) return;

    btn.addEventListener('click', function() {
        var assetId = this.dataset.assetId;
        btn.disabled = true;
        btn.textContent = 'Memproses...';

        fetch('{{ route("predictive.recalculate-asset", $asset->id) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Hitung Ulang';
            if (data.success) {
                window.location.reload();
            } else {
                alert('Gagal menghitung ulang.');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Hitung Ulang';
            alert('Terjadi kesalahan koneksi.');
        });
    });
})();
</script>
@endpush
