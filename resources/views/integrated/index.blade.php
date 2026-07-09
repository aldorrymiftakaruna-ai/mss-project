@extends('layouts.app')

@section('title', 'DSS Terintegrasi')
@section('page-title', 'DSS Terintegrasi')
@section('page-sub', 'Waterfall Predictive → Prescriptive → Cost → Rekomendasi Final')

@section('content')
<div class="space-y-6">

    {{-- ========== BAGIAN 1: PREDICTIVE (RISK SCORE) ========== --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-4">
            <span class="w-8 h-8 bg-[#0E9E8E]/10 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-[#0E9E8E]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </span>
            <h2 class="text-base font-bold text-gray-900">1. Predictive — Risk Scoring</h2>
            <span class="text-xs text-gray-400 ml-auto">Terakhir dihitung: otomatis</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-gray-50 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-gray-900">{{ $predictiveData['total'] }}</p>
                <p class="text-xs text-gray-500">Total Asset Dinilai</p>
            </div>
            <div class="bg-red-50 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-red-600">{{ $predictiveData['count_high'] }}</p>
                <p class="text-xs text-red-500">Risiko Tinggi</p>
            </div>
            <div class="bg-yellow-50 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-yellow-600">{{ $predictiveData['count_medium'] }}</p>
                <p class="text-xs text-yellow-500">Risiko Sedang</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4 text-center">
                <p class="text-2xl font-bold text-green-600">{{ $predictiveData['count_low'] }}</p>
                <p class="text-xs text-green-500">Risiko Rendah</p>
            </div>
        </div>

        @if($predictiveData['scores']->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left">
                            <th class="pb-2 font-semibold text-gray-600 text-xs uppercase tracking-wider">Asset</th>
                            <th class="pb-2 font-semibold text-gray-600 text-xs uppercase tracking-wider">Skor</th>
                            <th class="pb-2 font-semibold text-gray-600 text-xs uppercase tracking-wider">Kategori</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($predictiveData['scores']->take(5) as $score)
                            <tr class="border-b border-gray-100">
                                <td class="py-2 text-gray-900">{{ $score->asset?->tag_no ?? '—' }}</td>
                                <td class="py-2 text-gray-600">{{ $score->score }}</td>
                                <td class="py-2">
                                    @php
                                        $catClass = match($score->category) {
                                            'tinggi' => 'bg-red-100 text-red-700',
                                            'sedang' => 'bg-yellow-100 text-yellow-700',
                                            default => 'bg-green-100 text-green-700',
                                        };
                                    @endphp
                                    <span class="{{ $catClass }} text-xs px-2 py-0.5 rounded-full font-medium">
                                        {{ ucfirst($score->category) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                <a href="{{ route('predictive.index') }}" class="text-xs text-[#0E9E8E] hover:underline">Lihat detail →</a>
            </div>
        @else
            <p class="text-sm text-gray-400 text-center py-4">
                Belum ada data risk score. 
                <a href="{{ route('predictive.index') }}" class="text-[#0E9E8E] hover:underline">Hitung sekarang</a>.
            </p>
        @endif
    </div>

    {{-- ========== BAGIAN 2: PRESCRIPTIVE (AHP + TOPSIS) ========== --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center gap-3 mb-4">
            <span class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </span>
            <h2 class="text-base font-bold text-gray-900">2. Prescriptive — AHP + TOPSIS Ranking</h2>
            <span class="text-xs text-gray-400 ml-auto">
                @if($prescriptiveData['session'])
                    Sesi: {{ $prescriptiveData['session']->name }}
                @else
                    Belum ada sesi final
                @endif
            </span>
        </div>

        @if($prescriptiveData['session'])
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left">
                            <th class="pb-2 font-semibold text-gray-600 text-xs uppercase tracking-wider">Ranking</th>
                            <th class="pb-2 font-semibold text-gray-600 text-xs uppercase tracking-wider">Asset</th>
                            <th class="pb-2 font-semibold text-gray-600 text-xs uppercase tracking-wider">Skor</th>
                            <th class="pb-2 font-semibold text-gray-600 text-xs uppercase tracking-wider">d+</th>
                            <th class="pb-2 font-semibold text-gray-600 text-xs uppercase tracking-wider">d-</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($prescriptiveData['rankings']->take(5) as $r)
                            <tr class="border-b border-gray-100">
                                <td class="py-2 font-bold text-gray-900">#{{ $r->ranking }}</td>
                                <td class="py-2 text-gray-900">{{ $r->asset?->tag_no ?? '—' }}</td>
                                <td class="py-2 text-gray-600">{{ number_format($r->score, 4) }}</td>
                                <td class="py-2 text-gray-500">{{ number_format($r->d_plus, 4) }}</td>
                                <td class="py-2 text-gray-500">{{ number_format($r->d_minus, 4) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-2">
                <a href="{{ route('ahp.ranking', $prescriptiveData['session']->id) }}" class="text-xs text-[#0E9E8E] hover:underline">Lihat ranking lengkap →</a>
            </div>
        @else
            <p class="text-sm text-gray-400 text-center py-4">
                Belum ada sesi AHP final. 
                <a href="{{ route('ahp.index') }}" class="text-[#0E9E8E] hover:underline">Buat sesi AHP baru</a>.
            </p>
        @endif
    </div>

    {{-- ========== BAGIAN 3: REKOMENDASI FINAL ========== --}}
    <div class="bg-white rounded-xl border-2 border-[#0E9E8E]/20 p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <span class="w-8 h-8 bg-[#0E9E8E] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </span>
                <h2 class="text-base font-bold text-gray-900">5. Rekomendasi Final</h2>
                <span class="text-xs text-gray-400 ml-auto">
                    @php
                        $lastGenerated = $recommendations->first()?->generated_at;
                    @endphp
                    @if($lastGenerated)
                        Terakhir diperbarui: {{ $lastGenerated->format('d/m/Y H:i') }}
                    @endif
                </span>
            </div>
            <form method="POST" action="{{ route('dss.integrated.recalculate') }}" class="inline">
                @csrf
                <button type="submit" class="bg-[#0E9E8E] text-white px-4 py-1.5 rounded-lg text-xs font-medium hover:bg-[#0B8A7C] transition">
                    Perbarui Rekomendasi
                </button>
            </form>
        </div>

        @if($topRecommendation)
            <div class="bg-[#0E9E8E]/5 border border-[#0E9E8E]/20 rounded-lg p-4 mb-4">
                <div class="flex items-start gap-3">
                    <span class="w-6 h-6 bg-[#0E9E8E] rounded-full flex items-center justify-center text-white text-xs font-bold mt-0.5">1</span>
                    <div>
                        <p class="font-semibold text-gray-900">
                            {{ $topRecommendation->asset?->tag_no ?? '—' }}
                            <span class="text-xs text-gray-400 font-normal ml-2">Prioritas Tertinggi</span>
                        </p>
                        <p class="text-sm text-gray-600 mt-1">{{ $topRecommendation->description }}</p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-xs text-gray-400">Priority Score:</span>
                            <span class="text-sm font-bold text-[#0E9E8E]">{{ $topRecommendation->priority_score }}</span>
                            @php
                                $typeClass = match($topRecommendation->recommendation_type) {
                                    'predictive_maintenance' => 'bg-red-100 text-red-700',
                                    'prioritas_tinggi' => 'bg-orange-100 text-orange-700',
                                    'cost_optimization' => 'bg-blue-100 text-blue-700',
                                    'monitoring' => 'bg-yellow-100 text-yellow-700',
                                    default => 'bg-green-100 text-green-700',
                                };
                                $typeLabel = match($topRecommendation->recommendation_type) {
                                    'predictive_maintenance' => 'Predictive Maintenance',
                                    'prioritas_tinggi' => 'Prioritas Tinggi',
                                    'cost_optimization' => 'Optimasi Biaya',
                                    'monitoring' => 'Monitoring',
                                    default => 'Rutin',
                                };
                            @endphp
                            <span class="{{ $typeClass }} text-xs px-2 py-0.5 rounded-full font-medium">{{ $typeLabel }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($recommendations->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left">
                            <th class="pb-2 font-semibold text-gray-600 text-xs uppercase tracking-wider">#</th>
                            <th class="pb-2 font-semibold text-gray-600 text-xs uppercase tracking-wider">Asset</th>
                            <th class="pb-2 font-semibold text-gray-600 text-xs uppercase tracking-wider">Priority Score</th>
                            <th class="pb-2 font-semibold text-gray-600 text-xs uppercase tracking-wider">Tipe</th>
                            <th class="pb-2 font-semibold text-gray-600 text-xs uppercase tracking-wider">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recommendations as $i => $rec)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-2 font-bold text-gray-900">{{ $i + 1 }}</td>
                                <td class="py-2 text-gray-900">{{ $rec->asset?->tag_no ?? '—' }}</td>
                                <td class="py-2">
                                    <span class="font-semibold text-[#0E9E8E]">{{ $rec->priority_score }}</span>
                                </td>
                                <td class="py-2">
                                    @php
                                        $tClass = match($rec->recommendation_type) {
                                            'predictive_maintenance' => 'bg-red-100 text-red-700',
                                            'prioritas_tinggi' => 'bg-orange-100 text-orange-700',
                                            'cost_optimization' => 'bg-blue-100 text-blue-700',
                                            'monitoring' => 'bg-yellow-100 text-yellow-700',
                                            default => 'bg-green-100 text-green-700',
                                        };
                                        $tLabel = match($rec->recommendation_type) {
                                            'predictive_maintenance' => 'Pred. Maint.',
                                            'prioritas_tinggi' => 'Prioritas',
                                            'cost_optimization' => 'Optimasi',
                                            'monitoring' => 'Monitoring',
                                            default => 'Rutin',
                                        };
                                    @endphp
                                    <span class="{{ $tClass }} text-xs px-2 py-0.5 rounded-full font-medium">{{ $tLabel }}</span>
                                </td>
                                <td class="py-2 text-gray-500 max-w-xs truncate">{{ $rec->description }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-400 text-center py-4">
                Belum ada rekomendasi. Klik "Perbarui Rekomendasi" untuk menghitung.
            </p>
        @endif
    </div>

    {{-- Keterangan --}}
    <div class="text-xs text-gray-400 text-center">
        Waterfall Decision Support System — Menggabungkan output Predictive Risk Scoring dan AHP/TOPSIS 
        untuk menghasilkan rekomendasi prioritas perawatan.
    </div>
</div>
@endsection
