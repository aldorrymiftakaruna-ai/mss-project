@extends('layouts.app')

@section('title', 'Predictive Risk')
@section('page-title', 'Predictive Risk')
@section('page-sub', '— Trend-based risk scoring per equipment')

@push('header-actions')
<x-info-tooltip title="Bagaimana Skor Risiko Dihitung?">
    <ul class="list-disc pl-4 space-y-1">
        <li>Skor risiko dihitung berdasarkan tren historis data vibrasi dan temperatur tiap equipment menggunakan <strong>Linear Regression</strong>.</li>
        <li>Slope (kemiringan tren) dari vibrasi (Slope Vib) dan temperatur (Slope Temp) dihitung dari data condition monitoring / history sensor per equipment.</li>
        <li>Slope positif besar menunjukkan tren memburuk (risiko naik).</li>
        <li>Skor akhir (0&ndash;1) merupakan hasil normalisasi/kombinasi dari slope-slope tersebut, lalu dikategorikan menjadi <strong>Rendah / Sedang / Tinggi</strong> berdasarkan threshold tertentu.</li>
        <li><strong>Sumber data:</strong> histori pembacaan condition monitoring (vibrasi &amp; temperatur) per equipment, diambil dari record maintenance/monitoring terbaru.</li>
        <li>Tombol <strong>&ldquo;Hitung Ulang&rdquo;</strong> akan menjalankan ulang regresi menggunakan data terbaru yang tersedia.</li>
    </ul>
</x-info-tooltip>
@endpush

@section('content')
<div class="mb-5 flex items-center justify-between">
    <div>
        <p class="text-sm text-gray-600">Skor risiko dihitung dari tren vibrasi & temperatur menggunakan linear regression.</p>
    </div>
    <button id="btnRecalculate" class="inline-flex items-center gap-2 px-4 py-2 bg-[#0E9E8E] text-white rounded-lg hover:bg-[#0B8A7C] transition text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        Hitung Ulang
    </button>
</div>

@if(session('success'))
    <div class="mb-5 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">{{ session('success') }}</div>
@endif

{{-- KPI --}}
<div class="grid grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Dinilai</div>
        <div class="text-2xl font-bold text-gray-900">{{ $total }}</div>
        <div class="text-xs text-gray-400 mt-1">EQUIPMENT</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-red-200 bg-red-50">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Risiko Tinggi</div>
        <div class="text-2xl font-bold text-red-600">{{ $tinggi }}</div>
        <div class="text-xs text-red-400 mt-1">BUTUH TINDAKAN</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-amber-200 bg-amber-50">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Risiko Sedang</div>
        <div class="text-2xl font-bold text-amber-600">{{ $sedang }}</div>
        <div class="text-xs text-amber-400 mt-1">PANTAU</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-green-200 bg-green-50">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Risiko Rendah</div>
        <div class="text-2xl font-bold text-green-600">{{ $rendah }}</div>
        <div class="text-xs text-green-400 mt-1">NORMAL</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Rata-rata Skor</div>
        <div class="text-2xl font-bold text-gray-900">{{ number_format($avgScore, 3) }}</div>
        <div class="text-xs text-gray-400 mt-1">
            {{ $noCmCount }} equipment tanpa data CM
        </div>
    </div>
</div>

{{-- DAFTAR RISK SCORE --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">Daftar Risk Score Equipment</h2>
        <span class="text-xs text-gray-400">{{ $total }} equipment</span>
    </div>

    @if($riskScores->isEmpty())
        <div class="p-10 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p class="text-gray-500 text-sm">Belum ada data risk score. Klik "Hitung Ulang" untuk memproses.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase border-b border-gray-100 bg-gray-50">
                        <th class="text-left px-4 py-3">Tag No</th>
                        <th class="text-left px-4 py-3">Equipment</th>
                        <th class="text-left px-4 py-3">Company</th>
                        <th class="text-center px-4 py-3">Skor</th>
                        <th class="text-center px-4 py-3">Kategori</th>
                        <th class="text-center px-4 py-3">Slope Vib</th>
                        <th class="text-center px-4 py-3">Slope Temp</th>
                        <th class="text-center px-4 py-3">Level</th>
                        <th class="text-right px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($riskScores as $rs)
                    @php
                        $params = $rs->parameters_json ?? [];
                        $badgeColor = match($rs->category) {
                            'tinggi' => 'bg-red-100 text-red-700',
                            'sedang' => 'bg-amber-100 text-amber-700',
                            default  => 'bg-green-100 text-green-700',
                        };
                        $barColor = match($rs->category) {
                            'tinggi' => 'bg-red-500',
                            'sedang' => 'bg-amber-500',
                            default  => 'bg-green-500',
                        };
                        $vibSlope = $params['vibration_slope_norm'] ?? '-';
                        $tempSlope = $params['temperature_slope_norm'] ?? '-';
                        $level = $rs->category;
                    @endphp
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $rs->asset->tag_no ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-800 max-w-xs truncate">{{ $rs->asset->description ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $rs->asset->company->alias ?? '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center gap-2 justify-center">
                                <div class="w-16 h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $rs->score * 100 }}%;"></div>
                                </div>
                                <span class="text-xs font-mono font-semibold text-gray-700">{{ number_format($rs->score, 3) }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">{{ ucfirst($rs->category) }}</span>
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-xs text-gray-500">
                            {{ is_numeric($vibSlope) ? number_format($vibSlope, 3) : $vibSlope }}
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-xs text-gray-500">
                            {{ is_numeric($tempSlope) ? number_format($tempSlope, 3) : $tempSlope }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block w-3 h-3 rounded-full {{ $barColor }}"></span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('predictive.detail', $rs->asset_id) }}" class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition">Detail</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function() {
    var btn = document.getElementById('btnRecalculate');
    if (!btn) return;

    btn.addEventListener('click', function() {
        btn.disabled = true;
        btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Memproses...';

        fetch('{{ route("predictive.recalculate") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = 'Hitung Ulang';
            if (data.success) {
                window.location.reload();
            } else {
                alert('Gagal: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = 'Hitung Ulang';
            alert('Terjadi kesalahan koneksi.');
        });
    });
})();
</script>
@endpush
