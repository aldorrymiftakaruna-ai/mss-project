@extends('layouts.app')

@section('title', 'Forecasting Downtime')
@section('page-title', 'Forecasting')
@section('page-sub', 'Demonstrasi Exponential Smoothing (ES) dan Moving Average (MA)')

@section('content')
<div class="space-y-6">
    {{-- Panel Kontrol --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Parameter Forecasting</h3>
        <form method="GET" action="{{ route('forecast.calculate') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            {{-- Tipe Data --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Data</label>
                <select name="data_type"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-[#0E9E8E] focus:border-[#0E9E8E]">
                    <option value="downtime" {{ $dataType === 'downtime' ? 'selected' : '' }}>Downtime (menit)</option>
                    <option value="work_duration" {{ $dataType === 'work_duration' ? 'selected' : '' }}>Durasi Kerja (menit)</option>
                </select>
            </div>

            {{-- Model --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Model</label>
                <select name="model_type" id="modelType"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-[#0E9E8E] focus:border-[#0E9E8E]">
                    <option value="ES" {{ $modelType === 'ES' ? 'selected' : '' }}>Exponential Smoothing</option>
                    <option value="MA" {{ $modelType === 'MA' ? 'selected' : '' }}>Moving Average</option>
                </select>
            </div>

            {{-- Alpha (ES) --}}
            <div id="alphaField">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Alpha (α)</label>
                <input type="number" name="alpha" step="0.01" min="0.01" max="0.99"
                    value="{{ $alpha }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-[#0E9E8E] focus:border-[#0E9E8E]">
            </div>

            {{-- MA Period --}}
            <div id="maPeriodField" style="{{ $modelType === 'MA' ? '' : 'display:none' }}">
                <label class="block text-xs font-medium text-gray-600 mb-1.5">MA Period (n)</label>
                <input type="number" name="ma_period" min="2" max="12"
                    value="{{ $maPeriod }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-[#0E9E8E] focus:border-[#0E9E8E]">
            </div>

            {{-- Rentang Data --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Data (bulan)</label>
                <select name="months"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-[#0E9E8E] focus:border-[#0E9E8E]">
                    @foreach([6, 12, 18, 24, 36, 48, 60] as $m)
                        <option value="{{ $m }}" {{ $months === $m ? 'selected' : '' }}>{{ $m }} bulan</option>
                    @endforeach
                </select>
            </div>

            {{-- Forecast Count --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Forecast ke depan</label>
                <select name="forecast_count"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-[#0E9E8E] focus:border-[#0E9E8E]">
                    @foreach([1, 2, 3, 4, 5, 6] as $fc)
                        <option value="{{ $fc }}" {{ $forecastCount === $fc ? 'selected' : '' }}>{{ $fc }} periode</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-6 flex items-end">
                <button type="submit"
                    class="bg-[#0E9E8E] text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-[#0B8A7C] transition">
                    Hitung Forecast
                </button>
            </div>
        </form>
    </div>

    {{-- Error Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">MAE</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($metrics['mae'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Mean Absolute Error</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">MSE</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($metrics['mse'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Mean Squared Error</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">MAPE</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($metrics['mape'], 2) }}%</p>
            <p class="text-xs text-gray-400 mt-1">Mean Absolute Percentage Error</p>
        </div>
    </div>

    {{-- Grafik --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">
            Grafik: Actual vs Forecast
            <span class="text-xs text-gray-400 font-normal ml-2">
                ({{ $modelType === 'ES' ? "Exponential Smoothing α={$alpha}" : "Moving Average n={$maPeriod}" }})
            </span>
        </h3>
        <div style="position:relative; height:300px;">
            <canvas id="forecastChart"></canvas>
        </div>
    </div>

    {{-- Tabel Hasil --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900">Detail Hasil Forecasting</h3>
            <span class="text-xs text-gray-400">Satuan: menit</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left">
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Periode</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Actual</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Forecast</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Error</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">|Error|</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $r)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 font-medium text-gray-900">{{ $r->period }}</td>
                            <td class="py-3 text-gray-600">
                                {{ $r->actual !== null ? number_format($r->actual, 0) : '—' }}
                            </td>
                            <td class="py-3 text-gray-600">
                                {{ $r->forecast !== null ? number_format($r->forecast, 2) : '—' }}
                            </td>
                            <td class="py-3 {{ $r->error !== null ? ($r->error >= 0 ? 'text-green-600' : 'text-red-600') : 'text-gray-400' }}">
                                {{ $r->error !== null ? number_format($r->error, 2) : '—' }}
                            </td>
                            <td class="py-3 text-gray-600">
                                {{ $r->absolute_error !== null ? number_format($r->absolute_error, 2) : '—' }}
                            </td>
                            <td class="py-3">
                                @if($r->actual === null)
                                    <span class="bg-[#0E9E8E]/10 text-[#0E9E8E] text-xs px-2 py-0.5 rounded-full font-medium">Forecast</span>
                                @elseif($r->forecast === null)
                                    <span class="bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded-full font-medium">Data Awal</span>
                                @else
                                    <span class="bg-blue-100 text-blue-700 text-xs px-2 py-0.5 rounded-full font-medium">Validasi</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-400">
                                Data tidak mencukupi untuk forecasting. 
                                Pastikan ada data historis minimal 2 periode (ES) atau {{ $maPeriod }} periode (MA).
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
    // Toggle Alpha vs MA Period
    const modelSelect = document.getElementById('modelType');
    const alphaField = document.getElementById('alphaField');
    const maField = document.getElementById('maPeriodField');

    modelSelect.addEventListener('change', function() {
        if (this.value === 'ES') {
            alphaField.style.display = 'block';
            maField.style.display = 'none';
        } else {
            alphaField.style.display = 'none';
            maField.style.display = 'block';
        }
    });

    // Grafik Forecast
    const results = @json($results);
    if (results.length > 0) {
        const ctx = document.getElementById('forecastChart').getContext('2d');
        
        const labels = results.map(r => r.period);
        const actualData = results.map(r => r.actual);
        const forecastData = results.map(r => r.forecast);

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Actual',
                        data: actualData,
                        borderColor: '#0E9E8E',
                        backgroundColor: 'rgba(14, 158, 142, 0.1)',
                        fill: false,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: '#0E9E8E',
                    },
                    {
                        label: 'Forecast',
                        data: forecastData,
                        borderColor: '#F97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: '#F97316',
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
                        title: {
                            display: true,
                            text: 'Menit',
                            font: { size: 11 }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>
@endpush
