@extends('layouts.app')

@section('title', 'Hasil AHP - ' . $session->name)
@section('page-title', 'Hasil AHP: ' . $session->name)
@section('page-sub', '— Bobot kriteria & Consistency Ratio')

@section('content')
@if(session('success'))
    <div class="mb-5 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">{{ session('success') }}</div>
@endif

<div class="mb-5 flex items-center justify-between">
    <div class="text-xs text-gray-500">Sesi dibuat {{ $session->created_at->format('d/m/Y H:i') }}</div>
    <div class="flex items-center gap-2">
        <a href="{{ route('ahp.pairwise', $session->id) }}" class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition">Edit Pairwise</a>
        @if($session->is_final)
        <a href="{{ route('ahp.ranking', $session->id) }}" class="text-xs px-3 py-1.5 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition">Lihat Ranking TOPSIS</a>
        @endif
        <a href="{{ route('ahp.index') }}" class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition">&larr; Kembali</a>
    </div>
</div>

{{-- CONSISTENCY RATIO --}}
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">n (Kriteria)</div>
        <div class="text-2xl font-bold text-gray-900">{{ $result['n'] }}</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Lambda Max</div>
        <div class="text-2xl font-bold text-gray-900">{{ number_format($result['consistency']['lambda_max'] ?? $result['consistency']['cr'] * 2 + $result['n'], 4) }}</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Consistency Ratio (CR)</div>
        <div class="text-2xl font-bold {{ $session->is_final ? 'text-green-600' : 'text-red-600' }}">
            {{ number_format($session->consistency_ratio, 4) }}
        </div>
    </div>
    <div class="bg-white rounded-xl p-5 border {{ $session->is_final ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Status</div>
        <div class="text-lg font-bold {{ $session->is_final ? 'text-green-700' : 'text-red-700' }}">
            {{ $session->is_final ? 'Konsisten ✓' : 'Tidak Konsisten' }}
        </div>
        @if(!$session->is_final)
            <p class="text-xs text-red-500 mt-1">CR > 0.1. Edit pairwise untuk memperbaiki konsistensi.</p>
        @endif
    </div>
</div>

{{-- BOBOT KRITERIA --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
    {{-- Tabel Bobot --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Bobot Kriteria</h2>
        </div>
        <div class="p-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase border-b border-gray-100">
                        <th class="pb-2 text-left">Kriteria</th>
                        <th class="pb-2 text-right">Bobot</th>
                        <th class="pb-2 text-right">Priority Vector</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($result['criteria'] as $c)
                    <tr>
                        <td class="py-2 font-medium text-gray-800">{{ $c['label'] }}</td>
                        <td class="py-2 text-right font-mono text-sm text-gray-700">{{ number_format($c['weight'] * 100, 2) }}%</td>
                        <td class="py-2 text-right font-mono text-sm text-gray-500">{{ number_format($c['priority_vector'], 5) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($result['n'] > 0)
            @php
                $sorted = collect($result['criteria'])->sortByDesc('weight');
            @endphp
            <div class="mt-5 space-y-2">
                @foreach($sorted as $c)
                    @php $pct = $c['weight'] * 100; @endphp
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-gray-700">{{ $c['label'] }}</span>
                            <span class="text-gray-500">{{ number_format($pct, 1) }}%</span>
                        </div>
                        <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-[#0E9E8E] transition-all" style="width: {{ $pct }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Matriks Pairwise --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Matriks Pairwise</h2>
        </div>
        <div class="p-5 overflow-x-auto">
            @php
                $critLabels = $result['criteria'];
                $matrix = $result['matrix'];
                $n = $result['n'];
            @endphp
            @if($n > 0)
            <table class="w-full text-xs">
                <thead>
                    <tr>
                        <th class="p-1.5 text-gray-400 font-normal"></th>
                        @foreach($critLabels as $cl)
                        <th class="p-1.5 text-gray-600 font-medium text-center">{{ $cl['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @for($i = 0; $i < $n; $i++)
                    <tr>
                        <th class="p-1.5 text-gray-600 font-medium text-left pr-3">{{ $critLabels[$i]['label'] }}</th>
                        @for($j = 0; $j < $n; $j++)
                        <td class="p-1.5 text-center {{ $i == $j ? 'text-gray-300' : 'text-gray-600' }}">
                            {{ $i == $j ? '1' : number_format($matrix[$i][$j], 3) }}
                        </td>
                        @endfor
                    </tr>
                    @endfor
                </tbody>
            </table>
            @endif
        </div>
    </div>
</div>
@endsection
