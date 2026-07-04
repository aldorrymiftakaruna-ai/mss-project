@extends('layouts.app')

@section('title', 'Ranking TOPSIS - ' . $session->name)
@section('page-title', 'Ranking TOPSIS: ' . $session->name)
@section('page-sub', '— Peringkat equipment berdasarkan bobot AHP')

@section('content')
@if(session('error'))
    <div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{{ session('error') }}</div>
@endif

<div class="mb-5 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <span class="text-xs text-gray-500">Sesi: <strong>{{ $session->name }}</strong></span>
        <span class="text-xs text-gray-400">|</span>
        <span class="text-xs text-gray-500">Bobot: 
            @foreach($result['criteria'] as $c)
                {{ $c->label ?? $c->name }} ({{ number_format($c->weight * 100, 1) }}%)
                @if(!$loop->last) &middot; @endif
            @endforeach
        </span>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('ahp.result', $session->id) }}" class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition">Hasil AHP</a>
        <a href="{{ route('ahp.index') }}" class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition">&larr; Kembali</a>
    </div>
</div>

@if(isset($result['message']))
    <div class="bg-white rounded-xl border border-gray-200 p-10 text-center">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <p class="text-gray-500 text-sm">{{ $result['message'] }}</p>
    </div>
@else
    {{-- RINGKASAN --}}
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 border border-gray-200">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Equipment Dinilai</div>
            <div class="text-2xl font-bold text-gray-900">{{ count($result['rankings']) }}</div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Kriteria</div>
            <div class="text-2xl font-bold text-gray-900">{{ $result['criteria']->count() }}</div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Skor Tertinggi</div>
            <div class="text-2xl font-bold text-[#0E9E8E]">{{ $result['rankings'] ? number_format($result['rankings'][0]['score'], 4) : '—' }}</div>
        </div>
        <div class="bg-white rounded-xl p-5 border border-gray-200">
            <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Skor Terendah</div>
            <div class="text-2xl font-bold text-gray-600">{{ $result['rankings'] ? number_format($result['rankings'][count($result['rankings'])-1]['score'], 4) : '—' }}</div>
        </div>
    </div>

    {{-- TABEL RANKING --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Peringkat Equipment</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase border-b border-gray-100 bg-gray-50">
                        <th class="text-center px-4 py-3 w-12">Rank</th>
                        <th class="text-left px-4 py-3">Tag No</th>
                        <th class="text-left px-4 py-3">Equipment</th>
                        <th class="text-right px-4 py-3">D+</th>
                        <th class="text-right px-4 py-3">D-</th>
                        <th class="text-right px-4 py-3">Skor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($result['rankings'] as $r)
                    @php
                        $medal = $r['ranking'] == 1 ? '🥇' : ($r['ranking'] == 2 ? '🥈' : ($r['ranking'] == 3 ? '🥉' : ''));
                        $rowBg = $r['ranking'] <= 3 ? 'bg-amber-50/50' : '';
                    @endphp
                    <tr class="{{ $rowBg }} hover:bg-gray-50/50">
                        <td class="text-center px-4 py-3 font-bold text-gray-700">{{ $r['ranking'] }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $r['tag_no'] }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $r['description'] }}</td>
                        <td class="px-4 py-3 text-right font-mono text-xs text-gray-500">{{ number_format($r['distance_positive'], 4) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-xs text-gray-500">{{ number_format($r['distance_negative'], 4) }}</td>
                        <td class="px-4 py-3 text-right font-mono text-sm font-bold {{ $r['ranking'] == 1 ? 'text-[#0E9E8E]' : 'text-gray-700' }}">
                            {{ number_format($r['score'], 4) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- DATA MATRIKS (collapsible) --}}
    <div class="mt-5 bg-white rounded-xl border border-gray-200">
        <details class="group">
            <summary class="px-5 py-4 cursor-pointer flex items-center justify-between text-sm font-semibold text-gray-800 hover:bg-gray-50 rounded-xl">
                <span>Detail Matriks Keputusan & Normalisasi</span>
                <svg class="w-4 h-4 text-gray-400 group-open:rotate-180 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </summary>
            <div class="px-5 pb-5 space-y-5">
                {{-- Matriks Asli --}}
                <div>
                    <h4 class="text-xs font-semibold text-gray-600 uppercase mb-2">Matriks Keputusan</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-gray-100 text-gray-500">
                                    <th class="p-1.5 text-left">Asset</th>
                                    @foreach($result['criteria'] as $c)
                                    <th class="p-1.5 text-right">{{ $c->label ?? $c->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($result['matrix'] as $assetId => $row)
                                <tr class="border-b border-gray-50">
                                    <td class="p-1.5 font-mono text-gray-600">
                                        @php
                                            $a = \App\Models\Asset::find($assetId);
                                        @endphp
                                        {{ $a ? $a->tag_no : $assetId }}
                                    </td>
                                    @foreach($result['criteria'] as $c)
                                    <td class="p-1.5 text-right text-gray-600">{{ number_format($row[$c->name] ?? 0, 2) }}</td>
                                    @endforeach
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Bobot --}}
                <div>
                    <h4 class="text-xs font-semibold text-gray-600 uppercase mb-2">Bobot AHP</h4>
                    <div class="flex flex-wrap gap-3">
                        @foreach($result['criteria'] as $c)
                        <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">
                            {{ $c->label ?? $c->name }}: <strong>{{ number_format($c->weight * 100, 1) }}%</strong>
                        </span>
                        @endforeach
                    </div>
                </div>

                {{-- Solusi Ideal --}}
                @if(isset($result['ideal']))
                <div>
                    <h4 class="text-xs font-semibold text-gray-600 uppercase mb-2">Solusi Ideal</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium text-green-600 mb-1">Ideal Positif (A+)</p>
                            <div class="space-y-0.5">
                                @foreach($result['ideal']['positive'] as $name => $val)
                                <span class="text-xs text-gray-600 block">{{ $name }}: {{ number_format($val, 4) }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-red-600 mb-1">Ideal Negatif (A-)</p>
                            <div class="space-y-0.5">
                                @foreach($result['ideal']['negative'] as $name => $val)
                                <span class="text-xs text-gray-600 block">{{ $name }}: {{ number_format($val, 4) }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </details>
    </div>
@endif
@endsection
