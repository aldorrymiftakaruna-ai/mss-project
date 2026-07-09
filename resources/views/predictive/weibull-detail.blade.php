@extends('layouts.app')

@section('title', 'Weibull — ' . $asset->tag_no)
@section('page-title', 'Weibull Reliability — ' . $asset->tag_no)
@section('page-sub', $asset->description)

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

    @if(is_array($result) && isset($result['message']))
        {{-- Hasil dari service (array, belum tersimpan) --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            @if($result['beta'] !== null)
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-[#0E9E8E]/5 rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500 uppercase tracking-wider">β (Shape)</p>
                        <p class="text-2xl font-bold {{ $result['beta'] > 1 ? 'text-red-600' : 'text-green-600' }}">
                            {{ $result['beta'] }}
                        </p>
                        <p class="text-xs text-gray-400">{{ $result['beta'] > 1 ? 'Wear-out period' : 'Infant mortality' }}</p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500 uppercase tracking-wider">η (Scale)</p>
                        <p class="text-2xl font-bold text-blue-600">{{ $result['eta'] }} <span class="text-sm font-normal">hari</span></p>
                        <p class="text-xs text-gray-400">Characteristic life</p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500 uppercase tracking-wider">MTTF</p>
                        <p class="text-2xl font-bold text-purple-600">{{ number_format($result['mttf'], 0) }} <span class="text-sm font-normal">hari</span></p>
                        <p class="text-xs text-gray-400">Mean Time To Failure</p>
                    </div>
                    <div class="bg-amber-50 rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500 uppercase tracking-wider">R(30 hari)</p>
                        <p class="text-2xl font-bold {{ $result['reliability_at_period'] > 0.8 ? 'text-green-600' : ($result['reliability_at_period'] > 0.5 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($result['reliability_at_period'] * 100, 1) }}%
                        </p>
                        <p class="text-xs text-gray-400">Reliabilitas 30 hari ke depan</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('weibull.calculate-asset', $asset->id) }}" class="inline">
                    @csrf
                    <button type="submit" class="bg-[#0E9E8E] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#0B8A7C] transition">
                        Hitung Ulang
                    </button>
                </form>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-400 mb-3">{{ $result['message'] ?? 'Data tidak cukup.' }}</p>
                    <form method="POST" action="{{ route('weibull.calculate-asset', $asset->id) }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-[#0E9E8E] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#0B8A7C] transition">
                            Coba Hitung Ulang
                        </button>
                    </form>
                </div>
            @endif
        </div>
    @elseif(isset($result) && is_object($result))
        {{-- Hasil dari database (WeibullResult model) --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-[#0E9E8E]/5 rounded-lg p-4 text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">β (Shape)</p>
                    <p class="text-2xl font-bold {{ $result->beta && $result->beta > 1 ? 'text-red-600' : 'text-green-600' }}">
                        {{ $result->beta ?? '—' }}
                    </p>
                    <p class="text-xs text-gray-400">{{ $result->beta && $result->beta > 1 ? 'Wear-out period' : 'Infant mortality' }}</p>
                </div>
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">η (Scale)</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $result->eta ?? '—' }} <span class="text-sm font-normal">hari</span></p>
                    <p class="text-xs text-gray-400">Characteristic life</p>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">MTTF</p>
                    <p class="text-2xl font-bold text-purple-600">
                        {{ $result->mttf ? number_format($result->mttf, 0) : '—' }} <span class="text-sm font-normal">hari</span>
                    </p>
                    <p class="text-xs text-gray-400">Mean Time To Failure</p>
                </div>
                <div class="bg-amber-50 rounded-lg p-4 text-center">
                    <p class="text-xs text-gray-500 uppercase tracking-wider">R(30 hari)</p>
                    <p class="text-2xl font-bold {{ $result->reliability_at_period !== null ? ($result->reliability_at_period > 0.8 ? 'text-green-600' : ($result->reliability_at_period > 0.5 ? 'text-yellow-600' : 'text-red-600')) : '' }}">
                        {{ $result->reliability_at_period !== null ? number_format($result->reliability_at_period * 100, 1) . '%' : '—' }}
                    </p>
                    <p class="text-xs text-gray-400">Reliabilitas 30 hari ke depan</p>
                </div>
            </div>

            @if($result->calculated_at)
                <p class="text-xs text-gray-400 mb-4">Terakhir dihitung: {{ $result->calculated_at->format('d/m/Y H:i') }}</p>
            @endif

            <form method="POST" action="{{ route('weibull.calculate-asset', $asset->id) }}" class="inline">
                @csrf
                <button type="submit" class="bg-[#0E9E8E] text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-[#0B8A7C] transition">
                    Hitung Ulang
                </button>
            </form>

            <a href="{{ route('weibull.index') }}" class="inline-block ml-2 border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                Kembali
            </a>
        </div>

        {{-- Detail Parameter --}}
        @if($result->parameters_json)
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Parameter Detail</h3>
                <pre class="text-xs text-gray-600 bg-gray-50 rounded-lg p-4 overflow-x-auto">{{ json_encode($result->parameters_json, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif
    @else
        {{-- Tidak ada data --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="text-center py-8">
                <p class="text-gray-400 mb-3">Data Weibull tidak tersedia untuk asset ini.</p>
                <form method="POST" action="{{ route('weibull.calculate-asset', $asset->id) }}" class="inline">
                    @csrf
                    <button type="submit" class="bg-[#0E9E8E] text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-[#0B8A7C] transition">
                        Hitung Weibull
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
