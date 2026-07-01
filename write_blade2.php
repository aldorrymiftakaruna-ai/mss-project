<?php
$blade = <<<'BLADE'

{{-- ============================================================
     TAB PENGUKURAN
     ============================================================ --}}
<div id="content-measurements">
    <div class="bg-white rounded-xl border border-gray-200">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-100">
                <tr class="text-xs text-gray-500 uppercase">
                    <th class="px-5 py-3 text-left">Tanggal</th>
                    <th class="px-5 py-3 text-left">Equipment</th>
                    <th class="px-5 py-3 text-left">PT</th>
                    <th class="px-5 py-3 text-left">Diukur</th>
                    <th class="px-5 py-3 text-center">Drv Max Vib</th>
                    <th class="px-5 py-3 text-center">Drn Max Vib</th>
                    <th class="px-5 py-3 text-center">Status</th>
                    <th class="px-5 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($cms as $m)
                @php
                    $asset = $m->asset;
                    $driverMax = round(max($m->driver_de_vib_v ?? 0, $m->driver_de_vib_h ?? 0, $m->driver_de_vib_a ?? 0, $m->driver_nde_vib_v ?? 0, $m->driver_nde_vib_h ?? 0, $m->driver_nde_vib_a ?? 0), 2);
                    $drivenMax = round(max($m->driven_de_vib_v ?? 0, $m->driven_de_vib_h ?? 0, $m->driven_de_vib_a ?? 0, $m->driven_nde_vib_v ?? 0, $m->driven_nde_vib_h ?? 0, $m->driven_nde_vib_a ?? 0), 2);
                    $status = $asset ? $asset->calcStatusFromCm($m) : 'normal';
                    $statusColors = ['normal' => 'bg-green-100 text-green-700', 'alarm' => 'bg-amber-100 text-amber-700', 'danger' => 'bg-red-100 text-red-700'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-600 whitespace-nowrap">{{ $m->tanggal->format('d M Y') }}</td>
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-800">{{ $asset->tag_no ?? '—' }}</div>
                        <div class="text-xs text-gray-400">{{ $asset->description ?? '—' }}</div>
                    </td>
                    <td class="px-5 py-3 text-gray-500">{{ $asset->company->code ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $m->measured_by ?? '—' }}</td>
                    <td class="px-5 py-3 text-center {{ $driverMax >= 4.5 ? 'text-red-600 font-semibold' : 'text-gray-600' }}">{{ $driverMax > 0 ? $driverMax : '—' }}</td>
                    <td class="px-5 py-3 text-center {{ $drivenMax >= 4.5 ? 'text-red-600 font-semibold' : 'text-gray-600' }}">{{ $drivenMax > 0 ? $drivenMax : '—' }}</td>
                    <td class="px-5 py-3 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($status) }}</span></td>
                    <td class="px-5 py-3 text-center">
                        <form action="{{ route('cm.destroy', $m) }}" method="POST" onsubmit="return confirm('Hapus data CM {{ $m->tanggal->format('d M Y') }}?')">
                            @csrf @method('DELETE')
                            <button class="text-red-400 hover:text-red-600 text-xs">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-5 py-10 text-center text-gray-400">Belum ada data pengukuran.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ============================================================
     TAB TEMUAN VISUAL
     ============================================================ --}}
<div id="content-findings" class="hidden">
    <div class="bg-white rounded-xl border border-gray-200">
        <table class="w-full text-sm">
            <thead class="border-b border-gray-100">
                <tr class="text-xs text-gray-500 uppercase">
                    <th class="px-5 py-3 text-left">Tanggal</th>
                    <th class="px-5 py-3 text-left">Equipment</th>
                    <th class="px-5 py-3 text-left">PT</th>
                    <th class="px-5 py-3 text-left">Kategori</th>
                    <th class="px-5 py-3 text-left">Deskripsi</th>
                    <th class="px-5 py-3 text-left">Severity</th>
                    <th class="px-5 py-3 text-left">Status</th>
                    <th class="px-5 py-3 text-left">Dilaporkan</th>
                    <th class="px-5 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($findings as $f)
                @php
                    $scColors = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-amber-100 text-amber-700','high'=>'bg-red-100 text-red-700'];
                    $stColors = ['open'=>'bg-red-100 text-red-700','acknowledged'=>'bg-amber-100 text-amber-700','resolved'=>'bg-green-100 text-green-700'];
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3 text-gray-600 whitespace-nowrap">{{ $f->tanggal->format('d M Y') }}</td>
                    <td class="px-5 py-3">
                        <div class="font-medium text-gray-800">{{ $f->asset->tag_no ?? '—' }}</div>
                        <div class="text-xs text-gray-400">{{ $f->asset->description ?? '—' }}</div>
                    </td>
                    <td class="px-5 py-3 text-gray-500">{{ $f->asset->company->code ?? '—' }}</td>
                    <td class="px-5 py-3 capitalize text-gray-600">{{ str_replace('_', ' ', $f->kategori) }}</td>
                    <td class="px-5 py-3 text-gray-600 max-w-xs truncate">{{ $f->deskripsi }}</td>
                    <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $scColors[$f->severity] ?? 'bg-gray-100' }}">{{ ucfirst($f->severity) }}</span></td>
                    <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $stColors[$f->status] ?? 'bg-gray-100' }}">{{ ucfirst($f->status) }}</span></td>
                    <td class="px-5 py-3 text-gray-500">{{ $f->reporter->name ?? $f->reported_by ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <form action="{{ route('cm.findings.destroy', $f) }}" method="POST" onsubmit="return confirm('Hapus temuan ini?')">
                            @csrf @method('DELETE')
                            <button class="text-red-400 hover:text-red-600 text-xs">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-5 py-10 text-center text-gray-400">Belum ada temuan visual.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ============================================================
     MODAL TAMBAH DATA CM
     ============================================================ --}}
<div id="modal-add" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-lg p-6 max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Tambah Data CM</h3>
            <button onclick="document.getElementById('modal-add').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <div class="flex gap-2 mb-4">
            <button type="button" onclick="switchType('measurement')" id="type-measurement" class="flex-1 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium">Pengukuran</button>
            <button type="button" onclick="switchType('finding')" id="type-finding" class="flex-1 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 font-medium">Temuan Visual</button>
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
                        <option value="{{ $asset->id }}">{{ $asset->tag_no }} &mdash; {{ $asset->description }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Tanggal *</label>
                    <input type="date" name="tanggal" required value="{{ date('Y-m-d') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div id="fields-measurement" class="space-y-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Diukur Oleh</label>
                    <select name="measured_by" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">Pilih Teknisi</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="border border-gray-100 rounded-lg p-3">
                    <p class="text-xs font-semibold text-gray-600 mb-3 uppercase tracking-wider">Driver (Motor)</p>
                    <div class="grid grid-cols-3 gap-2">
                        <div><label class="text-xs text-gray-400">DE Vib V</label><input type="number" step="0.01" name="driver_de_vib_v" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">DE Vib H</label><input type="number" step="0.01" name="driver_de_vib_h" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">DE Vib A</label><input type="number" step="0.01" name="driver_de_vib_a" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">DE CF+</label><input type="number" step="0.01" name="driver_de_cf" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">DE Temp (°C)</label><input type="number" step="0.01" name="driver_de_temp" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Vib V</label><input type="number" step="0.01" name="driver_nde_vib_v" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Vib H</label><input type="number" step="0.01" name="driver_nde_vib_h" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Vib A</label><input type="number" step="0.01" name="driver_nde_vib_a" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE CF+</label><input type="number" step="0.01" name="driver_nde_cf" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Temp (°C)</label><input type="number" step="0.01" name="driver_nde_temp" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">Ampere (A)</label><input type="number" step="0.01" name="driver_ampere" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                    </div>
                </div>
                <div class="border border-gray-100 rounded-lg p-3">
                    <p class="text-xs font-semibold text-gray-600 mb-3 uppercase tracking-wider">Driven (Gearbox/Pump/dll)</p>
                    <div class="grid grid-cols-3 gap-2">
                        <div><label class="text-xs text-gray-400">DE Vib V</label><input type="number" step="0.01" name="driven_de_vib_v" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">DE Vib H</label><input type="number" step="0.01" name="driven_de_vib_h" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">DE Vib A</label><input type="number" step="0.01" name="driven_de_vib_a" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">DE CF+</label><input type="number" step="0.01" name="driven_de_cf" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">DE Temp (°C)</label><input type="number" step="0.01" name="driven_de_temp" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Vib V</label><input type="number" step="0.01" name="driven_nde_vib_v" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Vib H</label><input type="number" step="0.01" name="driven_nde_vib_h" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Vib A</label><input type="number" step="0.01" name="driven_nde_vib_a" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE CF+</label><input type="number" step="0.01" name="driven_nde_cf" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Temp (°C)</label><input type="number" step="0.01" name="driven_nde_temp" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                    </div>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Catatan</label>
                    <textarea name="catatan" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>
            </div>
            <div id="fields-finding" class="space-y-4 hidden">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Dilaporkan Oleh</label>
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
                    <textarea name="deskripsi" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">Simpan</button>
                <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')" class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">Batal</button>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================
     CHART.JS + SCRIPT
     ============================================================ --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
var barLabels = @json($barLabels);
var barGood   = @json($barGood);
var barAlarm  = @json($barAlarm);
var barDanger = @json($barDanger);
var topVibData = @json($topVib);
var topMaxVib  = {{ $topMaxVib }};
var trendChart = null;

function initTrendChart() {
    var ctx = document.getElementById('trendChart').getContext('2d');
    trendChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: barLabels,
            datasets: [
                { label: 'Normal', data: barGood, backgroundColor: '#22C55E', borderRadius: 2 },
                { label: 'Alarm',  data: barAlarm, backgroundColor: '#F59E0B', borderRadius: 2 },
                { label: 'Danger', data: barDanger, backgroundColor: '#EF4444', borderRadius: 2 },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { boxWidth: 12, padding: 12 } } },
            scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, title: { display: true, text: 'Jumlah Records' } } }
        }
    });
}

function initTopVibChart() {
    var ctx = document.getElementById('topVibChart').getContext('2d');
    var labels = topVibData.map(function(d) { return d.asset_tag + ' (' + d.company_code + ')'; });
    var values = topVibData.map(function(d) { return d.max_vib; });
    var colors = topVibData.map(function(d) { return d.status === 'danger' ? '#EF4444' : d.status === 'alarm' ? '#F59E0B' : '#22C55E'; });
    new Chart(ctx, {
        type: 'bar',
        data: { labels: labels, datasets: [{ data: values, backgroundColor: colors, borderRadius: 4 }] },
        options: {
            indexAxis: 'y', responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(ctx) { return ctx.parsed.x + ' mm/s - ' + topVibData[ctx.dataIndex].asset_desc; } } } },
            scales: { x: { beginAtZero: true, max: (topMaxVib * 1.15) || 10, title: { display: true, text: 'mm/s' } }, y: { grid: { display: false } } }
        }
    });
}

function initDonutCharts() {
    document.querySelectorAll('[id^="donut-"]').forEach(function(canvas) {
        var g = parseInt(canvas.dataset.good) || 0;
        var a = parseInt(canvas.dataset.alarm) || 0;
        var d = parseInt(canvas.dataset.danger) || 0;
        new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: { labels: ['Normal', 'Alarm', 'Danger'], datasets: [{ data: [g, a, d], backgroundColor: ['#22C55E', '#F59E0B', '#EF4444'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: true, cutout: '70%', plugins: { legend: { display: false }, tooltip: { enabled: (g + a + d) > 0 } } }
        });
    });
}

function switchTab(tab) {
    document.getElementById('content-measurements').classList.toggle('hidden', tab !== 'measurements');
    document.getElementById('content-findings').classList.toggle('hidden', tab !== 'findings');
    document.getElementById('tab-measurements').className = tab === 'measurements' ? 'px-4 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium transition' : 'px-4 py-2 text-sm rounded-lg bg-white border border-gray-200 text-gray-600 font-medium transition';
    document.getElementById('tab-findings').className = tab === 'findings' ? 'px-4 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium transition' : 'px-4 py-2 text-sm rounded-lg bg-white border border-gray-200 text-gray-600 font-medium transition';
}

function switchType(type) {
    document.getElementById('input-type').value = type;
    document.getElementById('fields-measurement').classList.toggle('hidden', type !== 'measurement');
    document.getElementById('fields-finding').classList.toggle('hidden', type !== 'finding');
    document.getElementById('type-measurement').className = type === 'measurement' ? 'flex-1 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium' : 'flex-1 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 font-medium';
    document.getElementById('type-finding').className = type === 'finding' ? 'flex-1 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium' : 'flex-1 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 font-medium';
}

async function updateTrend() {
    var range = document.getElementById('trend-range').value;
    var startInput = document.getElementById('trend-start');
    var endInput = document.getElementById('trend-end');
    if (range === 'custom') {
        startInput.classList.remove('hidden'); endInput.classList.remove('hidden');
        if (!startInput.value || !endInput.value) return;
    } else { startInput.classList.add('hidden'); endInput.classList.add('hidden'); }
    var params = new URLSearchParams({ range: range });
    if (range === 'custom') { params.set('start', startInput.value); params.set('end', endInput.value); }
    try {
        var res = await fetch('{{ route("cm.trend-data") }}?' + params.toString());
        var data = await res.json();
        if (data.error) { alert(data.error); return; }
        trendChart.data.labels = data.labels;
        trendChart.data.datasets[0].data = data.good;
        trendChart.data.datasets[1].data = data.alarm;
        trendChart.data.datasets[2].data = data.danger;
        trendChart.update();
    } catch(e) { console.error('Gagal update trend:', e); }
}

document.addEventListener('DOMContentLoaded', function() {
    initDonutCharts(); initTrendChart(); initTopVibChart();
});
</script>

@endsection
BLADE;
echo $blade;
