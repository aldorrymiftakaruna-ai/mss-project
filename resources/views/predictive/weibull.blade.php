@extends('layouts.app')

@section('title', 'Weibull Reliability')
@section('page-title', 'Weibull Reliability')
@section('page-sub', 'Analisis parameter Weibull (β, η, MTTF) untuk setiap asset')

@section('content')
<div class="space-y-6">
    {{-- Flash messages --}}
    @if(session('success'))
    <div class="px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
        {{ session('error') }}
    </div>
    @endif
    @if(session('info'))
    <div class="px-4 py-3 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg text-sm">
        {{ session('info') }}
    </div>
    @endif

    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500">
            Estimasi parameter Weibull menggunakan Median Rank Regression (MRR)
        </p>
        <form method="POST" action="{{ route('weibull.calculate-all') }}">
            @csrf
            <button type="submit" class="bg-[#0E9E8E] text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-[#0B8A7C] transition"
                onclick="return confirm('Hitung Weibull untuk semua asset?')">
                Hitung Semua
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-5">
        @if($results->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-left">
                            <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Asset</th>
                            <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Perusahaan</th>
                            <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">β (Shape)</th>
                            <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">η (Scale)</th>
                            <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">MTTF (hari)</th>
                            <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Reliabilitas 30 hr</th>
                            <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Terakhir</th>
                            <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $wr)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 font-medium text-gray-900">{{ $wr->asset?->tag_no ?? '—' }}</td>
                                <td class="py-3 text-gray-500">{{ $wr->asset?->company?->name ?? '—' }}</td>
                                <td class="py-3">
                                    <span class="font-semibold {{ $wr->beta && $wr->beta > 1 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $wr->beta ?? '—' }}
                                    </span>
                                    <span class="text-xs text-gray-400 ml-1">
                                        {{ $wr->beta && $wr->beta > 1 ? '(wear-out)' : '(infant)' }}
                                    </span>
                                </td>
                                <td class="py-3 text-gray-600">{{ $wr->eta ?? '—' }}</td>
                                <td class="py-3 text-gray-600">{{ $wr->mttf ? number_format($wr->mttf, 0) : '—' }}</td>
                                <td class="py-3">
                                    @if($wr->reliability_at_period !== null)
                                        <span class="font-semibold {{ $wr->reliability_at_period > 0.8 ? 'text-green-600' : ($wr->reliability_at_period > 0.5 ? 'text-yellow-600' : 'text-red-600') }}">
                                            {{ number_format($wr->reliability_at_period * 100, 1) }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="py-3 text-gray-400 text-xs">
                                    {{ $wr->calculated_at ? $wr->calculated_at->format('d/m/Y') : '—' }}
                                </td>
                                <td class="py-3">
                                    <a href="{{ route('weibull.detail', $wr->asset_id) }}" class="text-[#0E9E8E] hover:underline text-xs">Detail</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-gray-400 mb-3">Belum ada data Weibull.</p>
                <form method="POST" action="{{ route('weibull.calculate-all') }}" class="inline">
                    @csrf
                    <button type="submit" class="bg-[#0E9E8E] text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-[#0B8A7C] transition">
                        Hitung Semua Asset
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
