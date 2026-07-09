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
        <div class="flex gap-2">
        <button onclick="document.getElementById('modal-edit').classList.remove('hidden')"
            class="bg-[#0E9E8E] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#0a7a6d] transition">
            Edit Equipment
        </button>
        <form action="{{ route('assets.destroy', $asset) }}" method="POST"
            onsubmit="return confirm('Hapus equipment {{ $asset->tag_no }}? Semua data CM & maintenance terkait juga akan terhapus.')">
            @csrf @method('DELETE')
            <button class="border border-red-200 text-red-500 text-sm px-4 py-2 rounded-lg hover:bg-red-50 transition">
                Hapus
            </button>
        </form>
    </div>
</div>

{{-- SECTION 1 — Spesifikasi --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-800">Spesifikasi Equipment</h2>
        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $asset->statusColor() }}">
            {{ ucfirst($asset->status) }}
        </span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-x-8 gap-y-4 text-sm">
        <div><p class="text-xs text-gray-400 mb-0.5">Tag No</p><p class="font-mono font-semibold text-gray-800">{{ $asset->tag_no }}</p></div>
        <div><p class="text-xs text-gray-400 mb-0.5">PT</p><p class="text-gray-700">{{ $asset->company->code }} — {{ $asset->company->name }}</p></div>
        <div><p class="text-xs text-gray-400 mb-0.5">Deskripsi</p><p class="text-gray-700">{{ $asset->description }}</p></div>
        <div><p class="text-xs text-gray-400 mb-0.5">Model</p><p class="text-gray-700">{{ $asset->model ?? '—' }}</p></div>
        <div><p class="text-xs text-gray-400 mb-0.5">Serial Number</p><p class="text-gray-700">{{ $asset->serial_number ?? '—' }}</p></div>
        <div><p class="text-xs text-gray-400 mb-0.5">Head / Capacity</p><p class="text-gray-700">{{ $asset->head_capacity ?? '—' }}</p></div>
        <div><p class="text-xs text-gray-400 mb-0.5">Motor kW</p><p class="text-gray-700">{{ $asset->motor_kw ? $asset->motor_kw . ' kW' : '—' }}</p></div>
        <div><p class="text-xs text-gray-400 mb-0.5">Motor RPM</p><p class="text-gray-700">{{ $asset->motor_rpm ? number_format($asset->motor_rpm) . ' rpm' : '—' }}</p></div>
        <div><p class="text-xs text-gray-400 mb-0.5">Motor Ampere (FLA)</p><p class="text-gray-700">{{ $asset->motor_ampere ? $asset->motor_ampere . ' A' : '—' }}</p></div>
    </div>
</div>

{{-- SECTION 2 — MTBF --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-800">MTBF — Mean Time Between Failures</h2>
        <span class="text-xs text-gray-400">per tag</span>
    </div>
    @if(!$mtbf->ada_data)
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Belum ada data kegagalan</p>
                <p class="text-xs text-gray-400 mt-1">Tidak ada laporan corrective maintenance untuk equipment ini.</p>
            </div>
            <div class="text-right">
                <span class="text-3xl font-bold text-gray-300">—</span>
                <p class="text-xs text-gray-400 mt-0.5">hari</p>
            </div>
        </div>
    @elseif($mtbf->data_terbatas)
        @php
            $barColor = $mtbf->mtbf_hari < 30 ? 'bg-red-500' : ($mtbf->mtbf_hari < 90 ? 'bg-amber-500' : 'bg-[#0E9E8E]');
            $labelColor = $mtbf->mtbf_hari < 30 ? 'text-red-600' : ($mtbf->mtbf_hari < 90 ? 'text-amber-600' : 'text-[#0E9E8E]');
            $statusLabel = $mtbf->mtbf_hari < 30 ? 'Kritis' : ($mtbf->mtbf_hari < 90 ? 'Perlu Perhatian' : 'Normal');
        @endphp
        <div class="flex items-center justify-between mb-3">
            <div>
                <span class="text-3xl font-bold {{ $labelColor }}">{{ number_format($mtbf->mtbf_hari, 1) }} hari</span>
                <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium {{ $mtbf->mtbf_hari < 30 ? 'bg-red-100 text-red-700' : ($mtbf->mtbf_hari < 90 ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">{{ $statusLabel }}</span>
                <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Data Terbatas</span>
            </div>
            <div class="text-right text-xs text-gray-400">
                <div>{{ $mtbf->total_laporan }} laporan corrective</div>
                <div>Periode: {{ optional($mtbf->first_report)->format('d M Y') ?? '—' }} &ndash; sekarang</div>
            </div>
        </div>
        <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full {{ $barColor }} transition-all" style="width: {{ min($mtbf->mtbf_hari / 1.8, 100) }}%;"></div>
        </div>
        <div class="flex justify-between text-[10px] text-gray-400 mt-1">
            <span>0 hari</span>
            <span>30 hari (kritis)</span>
            <span>90 hari (normal)</span>
            <span>180 hari</span>
        </div>
        <p class="text-[10px] text-gray-400 mt-2">
            MTBF diperkirakan dari 1 laporan corrective saja — data masih terbatas, akurasi akan meningkat seiring bertambahnya laporan kegagalan.
        </p>
    @else
        @php
            $barColor = $mtbf->mtbf_hari < 30 ? 'bg-red-500' : ($mtbf->mtbf_hari < 90 ? 'bg-amber-500' : 'bg-[#0E9E8E]');
            $labelColor = $mtbf->mtbf_hari < 30 ? 'text-red-600' : ($mtbf->mtbf_hari < 90 ? 'text-amber-600' : 'text-[#0E9E8E]');
            $statusLabel = $mtbf->mtbf_hari < 30 ? 'Kritis' : ($mtbf->mtbf_hari < 90 ? 'Perlu Perhatian' : 'Normal');
        @endphp
        <div class="flex items-center justify-between mb-3">
            <div>
                <span class="text-3xl font-bold {{ $labelColor }}">{{ number_format($mtbf->mtbf_hari, 1) }} hari</span>
                <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium {{ $mtbf->mtbf_hari < 30 ? 'bg-red-100 text-red-700' : ($mtbf->mtbf_hari < 90 ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700') }}">{{ $statusLabel }}</span>
            </div>
            <div class="text-right text-xs text-gray-400">
                <div>{{ $mtbf->total_laporan }} laporan corrective</div>
                <div>Periode: {{ optional($mtbf->first_report)->format('d M Y') ?? '—' }} &ndash; sekarang</div>
            </div>
        </div>
        <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full {{ $barColor }} transition-all" style="width: {{ min($mtbf->mtbf_hari / 1.8, 100) }}%;"></div>
        </div>
        <div class="flex justify-between text-[10px] text-gray-400 mt-1">
            <span>0 hari</span>
            <span>30 hari (kritis)</span>
            <span>90 hari (normal)</span>
            <span>180 hari</span>
        </div>
        <p class="text-[10px] text-gray-400 mt-2">
            MTBF = rata-rata selisih hari antar laporan corrective maintenance, termasuk periode berjalan sampai hari ini. Semakin besar angkanya, semakin jarang equipment mengalami kegagalan.
        </p>
    @endif
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

{{-- SECTION 3 — History CM --}}
@php
    $thr = $asset->thresholds();
    $vibAlarm   = $thr['alarm'];
    $vibDanger  = $thr['danger'];
    $tempDanger = $thr['tempDanger'];
@endphp
<div class="bg-white rounded-xl border border-gray-200 mb-6">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">History Condition Monitoring</h2>
        <span class="text-xs text-gray-400">
            Alarm ≥ {{ $vibAlarm }} mm/s &nbsp;|&nbsp; Danger ≥ {{ $vibDanger }} mm/s &nbsp;|&nbsp; Temp ≥ {{ $tempDanger }}°C
        </span>
        </div>
    @php
                $sorted = $asset->cmMeasurements->sortByDesc('tanggal')->values();
        $totalCount = $sorted->count();
        $limitShow = 6;
        $limited = $sorted->take($limitShow);
        $hasMore = $totalCount > $limitShow;

        $chartData = $sorted->sortBy('tanggal')->values();
        $labels = $chartData->map(fn($c) => \Carbon\Carbon::parse($c->tanggal)->format('d M'));
        $driverVib = $chartData->map(fn($c) => round(max($c->driver_de_vib_v ?? 0, $c->driver_de_vib_h ?? 0, $c->driver_de_vib_a ?? 0, $c->driver_nde_vib_v ?? 0, $c->driver_nde_vib_h ?? 0, $c->driver_nde_vib_a ?? 0), 2));
        $drivenVib = $chartData->map(fn($c) => round(max($c->driven_de_vib_v ?? 0, $c->driven_de_vib_h ?? 0, $c->driven_de_vib_a ?? 0, $c->driven_nde_vib_v ?? 0, $c->driven_nde_vib_h ?? 0, $c->driven_nde_vib_a ?? 0), 2));
    @endphp

        @if($sorted->isEmpty())
        <p class="px-6 py-8 text-center text-gray-400 text-sm">Belum ada data CM.</p>
    @else
    {{-- Split 50:50: grafik kiri + tabel kanan --}}
        <div class="flex max-md:flex-col gap-0">
        {{-- KIRI: Grafik --}}
        <div class="w-3/8 max-md:w-full p-6 border-r border-gray-100 max-md:border-r-0 max-md:border-b">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700">Tren Max Vibration (mm/s)</h3>
                <div class="flex gap-3 text-xs">
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-blue-500"></span> Driver</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-orange-500"></span> Driven</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-0.5 bg-amber-400"></span> {{ $vibAlarm }}</span>
                    <span class="flex items-center gap-1"><span class="w-3 h-0.5 bg-red-500"></span> {{ $vibDanger }}</span>
                </div>
            </div>
            <div class="relative" style="height: 260px;">
                <canvas id="vibrationChart"></canvas>
            </div>
            <script>
                        new Chart(document.getElementById('vibrationChart'), {
                type: 'line',
                data: {
                    labels: @json($labels),
                    datasets: [
                        {
                            label: 'Driver Max Vib',
                            data: @json($driverVib),
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59,130,246,0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        },
                        {
                            label: 'Driven Max Vib',
                            data: @json($drivenVib),
                            borderColor: '#F97316',
                            backgroundColor: 'rgba(249,115,22,0.1)',
                            fill: true,
                            tension: 0.3,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        },
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y + ' mm/s'
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'mm/s' },
                            grid: { color: 'rgba(0,0,0,0.05)' },
                        },
                        x: {
                            grid: { display: false }
                        }
                    },
                },
                plugins: [{
                    id: 'thresholdLines',
                    beforeDraw(chart) {
                        const ctx = chart.ctx;
                        const yScale = chart.scales.y;
                        if (!yScale) return;
                        const drawLine = (value, color) => {
                            const y = yScale.getPixelForValue(value);
                            if (y > chart.chartArea.top && y < chart.chartArea.bottom) {
                                ctx.save();
                                ctx.beginPath();
                                ctx.setLineDash([6, 4]);
                                ctx.strokeStyle = color;
                                ctx.lineWidth = 2;
                                ctx.moveTo(chart.chartArea.left, y);
                                ctx.lineTo(chart.chartArea.right, y);
                                ctx.stroke();
                                ctx.restore();
                            }
                        };
                        drawLine({{ $vibAlarm }}, '#D97706');
                        drawLine({{ $vibDanger }}, '#DC2626');
                    }
                }]
            });
            </script>
        </div>

                {{-- KANAN: Tabel riwayat pengukuran --}}
                <div class="w-5/8 max-md:w-full overflow-x-auto flex flex-col">
                    <h3 class="text-sm font-semibold text-gray-700 px-4 pt-5 pb-2">Riwayat Pengukuran</h3>
            <div class="flex-1 overflow-y-auto">
            <table class="w-full text-[10px]">
                <thead>
                    <tr class="text-gray-400 uppercase border-b border-gray-100">
                                                <th class="px-3 py-2 text-left whitespace-nowrap">Tgl</th>
                        <th class="px-3 py-2 text-center border-l border-gray-50" colspan="3">Drv DE</th>
                        <th class="px-3 py-2 text-center border-l border-gray-50" colspan="3">Drv NDE</th>
                        <th class="px-3 py-2 text-center border-l border-gray-50" colspan="3">Drn DE</th>
                        <th class="px-3 py-2 text-center border-l border-gray-50" colspan="3">Drn NDE</th>
                        <th class="px-3 py-2 text-center border-l border-gray-50">Tmp</th>
                        <th class="px-3 py-2 text-center border-l border-gray-50">Amp</th>
                    </tr>
                    <tr class="text-gray-400 uppercase border-b border-gray-100 text-[9px]">
                        <th></th>
                        <th class="px-1 py-1 text-center">V</th>
                        <th class="px-1 py-1 text-center">H</th>
                        <th class="px-1 py-1 text-center border-r border-gray-50">A</th>
                        <th class="px-1 py-1 text-center">V</th>
                        <th class="px-1 py-1 text-center">H</th>
                        <th class="px-1 py-1 text-center border-r border-gray-50">A</th>
                        <th class="px-1 py-1 text-center">V</th>
                        <th class="px-1 py-1 text-center">H</th>
                        <th class="px-1 py-1 text-center border-r border-gray-50">A</th>
                        <th class="px-1 py-1 text-center">V</th>
                        <th class="px-1 py-1 text-center">H</th>
                        <th class="px-1 py-1 text-center border-r border-gray-50">A</th>
                        <th class="px-1 py-1 text-center"></th>
                        <th class="px-1 py-1 text-center"></th>
                    </tr>
                </thead>
                <tbody id="cm-tbody" class="divide-y divide-gray-50">
                    @foreach($limited as $cm)
                    @php
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
                        <td class="px-3 py-1.5 whitespace-nowrap text-gray-700">{{ \Carbon\Carbon::parse($cm->tanggal)->format('d/m/y') }}</td>
                                              <td class="px-2 py-1.5 text-center {{ $vc($cm->driver_de_vib_v) }}">{{ $cm->driver_de_vib_v ?? '—' }}</td>
                        <td class="px-2 py-1.5 text-center {{ $vc($cm->driver_de_vib_h) }}">{{ $cm->driver_de_vib_h ?? '—' }}</td>
                        <td class="px-2 py-1.5 text-center {{ $vc($cm->driver_de_vib_a) }} border-r border-gray-50">{{ $cm->driver_de_vib_a ?? '—' }}</td>
                        <td class="px-2 py-1.5 text-center {{ $vc($cm->driver_nde_vib_v) }}">{{ $cm->driver_nde_vib_v ?? '—' }}</td>
                        <td class="px-2 py-1.5 text-center {{ $vc($cm->driver_nde_vib_h) }}">{{ $cm->driver_nde_vib_h ?? '—' }}</td>
                        <td class="px-2 py-1.5 text-center {{ $vc($cm->driver_nde_vib_a) }} border-r border-gray-50">{{ $cm->driver_nde_vib_a ?? '—' }}</td>
                        <td class="px-2 py-1.5 text-center {{ $vc($cm->driven_de_vib_v) }}">{{ $cm->driven_de_vib_v ?? '—' }}</td>
                        <td class="px-2 py-1.5 text-center {{ $vc($cm->driven_de_vib_h) }}">{{ $cm->driven_de_vib_h ?? '—' }}</td>
                        <td class="px-2 py-1.5 text-center {{ $vc($cm->driven_de_vib_a) }} border-r border-gray-50">{{ $cm->driven_de_vib_a ?? '—' }}</td>
                        <td class="px-2 py-1.5 text-center {{ $vc($cm->driven_nde_vib_v) }}">{{ $cm->driven_nde_vib_v ?? '—' }}</td>
                        <td class="px-2 py-1.5 text-center {{ $vc($cm->driven_nde_vib_h) }}">{{ $cm->driven_nde_vib_h ?? '—' }}</td>
                        <td class="px-2 py-1.5 text-center {{ $vc($cm->driven_nde_vib_a) }} border-r border-gray-50">{{ $cm->driven_nde_vib_a ?? '—' }}</td>
                        <td class="px-2 py-1.5 text-center {{ $tc($cm->driver_de_temp) }}">{{ $cm->driver_de_temp ?? '—' }}</td>
                        <td class="px-2 py-1.5 text-center">{{ $cm->driver_ampere ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($hasMore)
            <div class="text-center py-3 border-t border-gray-100">
                <button onclick="toggleCmTable()" id="btn-cm-toggle" class="text-[#0E9E8E] text-xs hover:underline font-medium">
                    Show More ({{ $totalCount - $limitShow }})
                </button>
            </div>
                        @endif
            </div>{{-- .flex-1 --}}
        </div>{{-- .w-1/2 kanan --}}
    </div>{{-- .flex split --}}

    @php
    $allCmRows = $sorted->map(function($cm) use ($vibAlarm, $vibDanger, $tempDanger) {
        $vc = function($val) use ($vibAlarm, $vibDanger) {
            if ($val === null || $val === '') return '';
            return (float)$val >= $vibDanger ? 'bg-red-100 text-red-700 font-semibold' : ((float)$val >= $vibAlarm ? 'bg-amber-100 text-amber-700 font-semibold' : '');
        };
        $tc = function($val) use ($tempDanger) {
            if ($val === null || $val === '') return '';
            return (float)$val >= $tempDanger ? 'bg-red-100 text-red-700 font-semibold' : '';
        };
                return [
            \Carbon\Carbon::parse($cm->tanggal)->format('d/m/y'),
              $vc($cm->driver_de_vib_v), $cm->driver_de_vib_v ?? '—',
            $vc($cm->driver_de_vib_h), $cm->driver_de_vib_h ?? '—',
            $vc($cm->driver_de_vib_a), $cm->driver_de_vib_a ?? '—',
            $vc($cm->driver_nde_vib_v), $cm->driver_nde_vib_v ?? '—',
            $vc($cm->driver_nde_vib_h), $cm->driver_nde_vib_h ?? '—',
            $vc($cm->driver_nde_vib_a), $cm->driver_nde_vib_a ?? '—',
            $vc($cm->driven_de_vib_v), $cm->driven_de_vib_v ?? '—',
            $vc($cm->driven_de_vib_h), $cm->driven_de_vib_h ?? '—',
            $vc($cm->driven_de_vib_a), $cm->driven_de_vib_a ?? '—',
            $vc($cm->driven_nde_vib_v), $cm->driven_nde_vib_v ?? '—',
            $vc($cm->driven_nde_vib_h), $cm->driven_nde_vib_h ?? '—',
            $vc($cm->driven_nde_vib_a), $cm->driven_nde_vib_a ?? '—',
            $tc($cm->driver_de_temp), $cm->driver_de_temp ?? '—',
            $cm->driver_ampere ?? '—',
        ];
    });
    @endphp
    <script>
    let allCmRows = @json($allCmRows);
    let showingAll = false;
    let limitShow = {{ $limitShow }};

    function toggleCmTable() {
        const tbody = document.getElementById('cm-tbody');
        const btn = document.getElementById('btn-cm-toggle');
        if (!showingAll) {
            tbody.innerHTML = '';
            allCmRows.forEach(function(r) {
                                tbody.innerHTML += '<tr class="hover:bg-gray-50">' +
                    '<td class="px-3 py-1.5 whitespace-nowrap text-gray-700">' + r[0] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[1] + '">' + r[2] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[3] + '">' + r[4] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[5] + ' border-r border-gray-50">' + r[6] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[7] + '">' + r[8] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[9] + '">' + r[10] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[11] + ' border-r border-gray-50">' + r[12] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[13] + '">' + r[14] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[15] + '">' + r[16] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[17] + ' border-r border-gray-50">' + r[18] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[19] + '">' + r[20] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[21] + '">' + r[22] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[23] + ' border-r border-gray-50">' + r[24] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[25] + '">' + r[26] + '</td>' +
                    '<td class="px-2 py-1.5 text-center">' + r[27] + '</td></tr>';
            });
            btn.textContent = 'Show Less';
            showingAll = true;
        } else {
            tbody.innerHTML = '';
            allCmRows.slice(0, limitShow).forEach(function(r) {
                                tbody.innerHTML += '<tr class="hover:bg-gray-50">' +
                    '<td class="px-3 py-1.5 whitespace-nowrap text-gray-700">' + r[0] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[1] + '">' + r[2] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[3] + '">' + r[4] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[5] + ' border-r border-gray-50">' + r[6] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[7] + '">' + r[8] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[9] + '">' + r[10] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[11] + ' border-r border-gray-50">' + r[12] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[13] + '">' + r[14] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[15] + '">' + r[16] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[17] + ' border-r border-gray-50">' + r[18] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[19] + '">' + r[20] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[21] + '">' + r[22] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[23] + ' border-r border-gray-50">' + r[24] + '</td>' +
                    '<td class="px-2 py-1.5 text-center ' + r[25] + '">' + r[26] + '</td>' +
                    '<td class="px-2 py-1.5 text-center">' + r[27] + '</td></tr>';
            });
            btn.textContent = 'Show More (' + (allCmRows.length - limitShow) + ')';
            showingAll = false;
        }
    }
    </script>
    @endif
</div>

{{-- SECTION 3 — CM Findings --}}
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
                    <th class="px-5 py-3 text-left">ID Temuan</th>
                    <th class="px-5 py-3 text-left">Tanggal</th>
                    <th class="px-5 py-3 text-left">Kategori</th>
                    <th class="px-5 py-3 text-center">Severity</th>
                    <th class="px-5 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($asset->cmFindings->take(15) as $finding)
                @php
                    $sevColor = match(strtolower($finding->severity ?? '')) {
                        'high'   => 'bg-red-100 text-red-700',
                        'medium' => 'bg-amber-100 text-amber-700',
                        'low'    => 'bg-green-100 text-green-700',
                        default  => '',
                    };
                    $stColor = match(strtolower($finding->status ?? '')) {
                        'open'   => 'bg-red-100 text-red-700',
                        'closed' => 'bg-green-100 text-green-700',
                        default  => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 whitespace-nowrap">
                        <a href="{{ route('cm.findings.show', $finding) }}" class="font-mono text-xs font-semibold text-[#0E9E8E] hover:underline">
                            {{ $finding->finding_code ?? '—' }}
                        </a>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-gray-700">{{ \Carbon\Carbon::parse($finding->tanggal)->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-gray-600 capitalize">{{ $finding->kategori ?? '—' }}</td>
                    <td class="px-5 py-3 text-center">
                        @if($finding->severity)
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sevColor }}">{{ ucfirst($finding->severity) }}</span>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $stColor }}">{{ ucfirst($finding->status ?? '—') }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>


{{-- SECTION 5 — Maintenance Reports --}}
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
                    <th class="px-5 py-3 text-left">Kode Laporan</th>
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
                        'selesai' => 'bg-green-100 text-green-700',
                        'belum_selesai'      => 'bg-red-100 text-red-700',
                        default                => 'bg-gray-100 text-gray-600',
                    };
                @endphp
                <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('maintenance.show', $report) }}'">
                    <td class="px-5 py-3 whitespace-nowrap">
                        <span class="font-mono text-xs font-semibold text-[#0E9E8E]">{{ $report->report_code ?? '#' . $report->id }}</span>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-gray-700">{{ \Carbon\Carbon::parse($report->tanggal)->format('d M Y') }}</td>
                    <td class="px-5 py-3 text-gray-600 capitalize">{{ $report->shift ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600 capitalize">{{ $report->jenis ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-700 max-w-[200px] truncate" title="{{ $report->deskripsi_masalah }}">{{ $report->deskripsi_masalah ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600 max-w-[180px] truncate" title="{{ $report->tindakan }}">{{ $report->tindakan ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $report->reporter->name ?? '—' }}</td>
                    <td class="px-5 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sc }}">{{ ucfirst($report->status ?? '—') }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- SECTION 5 — BOM Spare Part --}}
<div class="bg-white rounded-xl border border-gray-200 mb-6">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">BOM Spare Part</h2>
        <button onclick="document.getElementById('modal-bom').classList.remove('hidden')"
            class="text-sm text-[#0E9E8E] hover:underline">+ Tambah Part</button>
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
                    <th class="px-5 py-3 text-center">Satuan</th>
                    <th class="px-5 py-3 text-center">Qty Kebutuhan</th>
                    <th class="px-5 py-3 text-center">Stok Tersedia</th>
                    <th class="px-5 py-3 text-center">Stok Min</th>
                    <th class="px-5 py-3 text-left">Keterangan</th>
                    <th class="px-5 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($asset->spareParts as $part)
                @php $stokOk = $part->stok_tersedia >= ($part->pivot->jumlah_kebutuhan ?? 0); @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <a href="{{ route('spareparts.show', $part) }}"
                           class="font-mono text-xs font-semibold text-[#0E9E8E] hover:underline">
                            {{ $part->kode_material }}
                        </a>
                    </td>
                    <td class="px-5 py-3 text-gray-800">{{ $part->deskripsi }}</td>
                    <td class="px-5 py-3 text-center text-gray-500">{{ $part->satuan ?? '—' }}</td>
                    <td class="px-5 py-3 text-center text-gray-700">{{ $part->pivot->jumlah_kebutuhan ?? '—' }}</td>
                    <td class="px-5 py-3 text-center font-medium {{ $stokOk ? 'text-green-600' : 'text-red-600' }}">{{ $part->stok_tersedia ?? 0 }}</td>
                    <td class="px-5 py-3 text-center text-gray-500">{{ $part->stok_minimum ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500 text-xs">{{ $part->pivot->keterangan ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <form action="{{ route('assets.spareparts.detach', [$asset, $part]) }}" method="POST"
                            onsubmit="return confirm('Hapus {{ $part->kode_material }} dari BOM?')">
                            @csrf @method('DELETE')
                            <button class="text-red-400 hover:text-red-600 text-xs">Hapus</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- MODAL TAMBAH BOM --}}
<div id="modal-bom" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Tambah Spare Part ke BOM</h3>
            <button onclick="document.getElementById('modal-bom').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form action="{{ route('assets.spareparts.attach', $asset) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Spare Part *</label>
                <select name="spare_part_id" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">Pilih spare part...</option>
                    @foreach($availableParts as $sp)
                    <option value="{{ $sp->id }}">{{ $sp->kode_material }} — {{ $sp->deskripsi }} (stok: {{ $sp->stok_tersedia }})</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Spare part PT {{ $asset->company->code }} ({{ $availableParts->count() }} part)</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Qty Kebutuhan *</label>
                    <input type="number" name="jumlah_kebutuhan" required min="1" value="1"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Keterangan</label>
                    <input type="text" name="keterangan" placeholder="XWD5-35, Bearing DE, dll"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">
                    Tambahkan
                </button>
                <button type="button"
                    onclick="document.getElementById('modal-bom').classList.add('hidden')"
                    class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL EDIT --}}
<div id="modal-edit" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Edit Equipment — {{ $asset->tag_no }}</h3>
            <button onclick="document.getElementById('modal-edit').classList.add('hidden')"
                class="text-gray-400 hover:text-gray-600 text-xl leading-none">✕</button>
        </div>
        <form action="{{ route('assets.update', $asset) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
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
