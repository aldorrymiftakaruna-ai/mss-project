@extends('layouts.app')

@section('title', 'Analisis Biaya Maintenance')
@section('page-title', 'Analisis Biaya')
@section('page-sub', 'Ringkasan biaya downtime, overtime, tenaga kerja, dan sparepart')

@section('content')
<div class="space-y-6">
    {{-- Filter Perusahaan --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <form method="GET" class="flex items-end gap-4">
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Filter Perusahaan</label>
                <select name="company_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-[#0E9E8E] focus:border-[#0E9E8E]">
                    <option value="">Semua Perusahaan</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ (int) $selectedCompanyId === $company->id ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-[#0E9E8E] text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-[#0B8A7C] transition">
                Terapkan
            </button>
            <a href="{{ route('cost.settings') }}" class="border border-gray-300 text-gray-700 px-5 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                Atur Rate
            </a>
            <a href="{{ route('cost.reanalyze-all') }}" class="border border-yellow-300 text-yellow-700 px-5 py-2 rounded-lg text-sm font-medium hover:bg-yellow-50 transition"
               onclick="return confirm('Analisis ulang semua laporan yang belum dianalisis?')">
                Analisis Ulang
            </a>
        </form>
    </div>

    {{-- Kartu Total Biaya --}}
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Total Biaya</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">Rp {{ number_format($totalCosts['grand_total'], 0, ',', '.') }}</p>
            <p class="text-xs text-gray-400 mt-1">Keseluruhan</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Downtime</p>
            <p class="text-2xl font-bold text-orange-600 mt-1">Rp {{ number_format($totalCosts['total_downtime'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Overtime</p>
            <p class="text-2xl font-bold text-red-600 mt-1">Rp {{ number_format($totalCosts['total_overtime'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Tenaga Kerja</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">Rp {{ number_format($totalCosts['total_labor'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Sparepart</p>
            <p class="text-2xl font-bold text-purple-600 mt-1">Rp {{ number_format($totalCosts['total_sparepart'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Grafik dan Tabel --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Chart Bulanan --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Tren Biaya Bulanan</h3>
            <div style="position:relative; height:280px;">
                <canvas id="costChart"></canvas>
            </div>
        </div>

        {{-- Breakdown Pie --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Komposisi Biaya</h3>
            <div style="position:relative; height:280px;">
                <canvas id="pieChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Tabel Analisis Terbaru --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">Analisis Terbaru</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left">
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Kode Laporan</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Asset</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Downtime</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Overtime</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Tenaga Kerja</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Sparepart</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Total</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAnalyses as $analysis)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 font-medium text-gray-900">
                                {{ $analysis->maintenanceReport?->report_code ?? '—' }}
                            </td>
                            <td class="py-3 text-gray-600">
                                {{ $analysis->maintenanceReport?->asset?->tag_no ?? '—' }}
                            </td>
                            <td class="py-3 text-orange-600">Rp {{ number_format($analysis->downtime_cost, 0, ',', '.') }}</td>
                            <td class="py-3 text-red-600">Rp {{ number_format($analysis->overtime_cost, 0, ',', '.') }}</td>
                            <td class="py-3 text-blue-600">Rp {{ number_format($analysis->labor_cost, 0, ',', '.') }}</td>
                            <td class="py-3 text-purple-600">Rp {{ number_format($analysis->sparepart_cost, 0, ',', '.') }}</td>
                            <td class="py-3 font-semibold text-gray-900">Rp {{ number_format($analysis->total_cost, 0, ',', '.') }}</td>
                            <td class="py-3 text-gray-500">
                                {{ $analysis->analyzed_at ? $analysis->analyzed_at->format('d M Y') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-gray-400">
                                Belum ada data analisis biaya. 
                                <a href="{{ route('cost.reanalyze-all') }}" class="text-[#0E9E8E] hover:underline">Analisis sekarang</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const monthlyData = @json($monthlySummary);

    // Grafik garis tren bulanan
    const ctx = document.getElementById('costChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.bulan),
            datasets: [
                {
                    label: 'Downtime',
                    data: monthlyData.map(d => d.total_downtime),
                    borderColor: '#F97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    borderWidth: 2,
                },
                {
                    label: 'Overtime',
                    data: monthlyData.map(d => d.total_overtime),
                    borderColor: '#EF4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    borderWidth: 2,
                },
                {
                    label: 'Tenaga Kerja',
                    data: monthlyData.map(d => d.total_labor),
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    borderWidth: 2,
                },
                {
                    label: 'Sparepart',
                    data: monthlyData.map(d => d.total_sparepart),
                    borderColor: '#8B5CF6',
                    backgroundColor: 'rgba(139, 92, 246, 0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                    borderWidth: 2,
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, padding: 12, font: { size: 11 } }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return 'Rp ' + value.toLocaleString('id-ID'); }
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Grafik pie komposisi biaya
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    const totals = @json($totalCosts);
    new Chart(pieCtx, {
        type: 'doughnut',
        data: {
            labels: ['Downtime', 'Overtime', 'Tenaga Kerja', 'Sparepart'],
            datasets: [{
                data: [totals.total_downtime, totals.total_overtime, totals.total_labor, totals.total_sparepart],
                backgroundColor: ['#F97316', '#EF4444', '#3B82F6', '#8B5CF6'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, padding: 12, font: { size: 11 } }
                }
            }
        }
    });
});
</script>
@endpush
