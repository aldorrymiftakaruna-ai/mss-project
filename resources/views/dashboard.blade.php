@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-sub', '— Ringkasan hari ini')

@section('content')

{{-- KPI Cards --}}
<div class="grid grid-cols-4 gap-5 mb-6">
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Total Equipment</div>
        <div class="text-3xl font-bold text-gray-900">{{ $totalAssets }}</div>
        <div class="text-xs text-gray-400 mt-1">CES & NPA</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Breakdown Aktif</div>
        <div class="text-3xl font-bold text-red-500">{{ $breakdown }}</div>
        <div class="text-xs text-gray-400 mt-1">Perlu tindakan segera</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Laporan Hari Ini</div>
        <div class="text-3xl font-bold text-[#0E9E8E]">{{ $laporanHariIni }}</div>
        <div class="text-xs text-gray-400 mt-1">Via Telegram bot</div>
    </div>
    <div class="bg-white rounded-xl p-5 border border-gray-200">
        <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Stok Kritis</div>
        <div class="text-3xl font-bold text-amber-500">{{ $stokKritis }}</div>
        <div class="text-xs text-gray-400 mt-1">Spare part di bawah minimum</div>
    </div>
</div>

{{-- Equipment Status & Rekomendasi DSS --}}
<div class="grid grid-cols-3 gap-5">

    {{-- Equipment Status --}}
    <div class="col-span-2 bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Status Equipment</h2>
            <a href="{{ route('assets.index') }}" class="text-xs text-[#0E9E8E] hover:underline">Lihat semua →</a>
        </div>
        <div class="p-5">
            @if($assets->isEmpty())
                <p class="text-gray-400 text-sm text-center py-8">Belum ada data equipment.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs text-gray-500 uppercase border-b border-gray-100">
                            <th class="pb-2 text-left">Tag No</th>
                            <th class="pb-2 text-left">Nama</th>
                            <th class="pb-2 text-left">PT</th>
                            <th class="pb-2 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($assets as $asset)
                        <tr>
                            <td class="py-2 font-mono text-xs text-gray-600">{{ $asset->tag_no }}</td>
                            <td class="py-2 text-gray-800">{{ $asset->name }}</td>
                            <td class="py-2 text-gray-500">{{ $asset->company->code }}</td>
                            <td class="py-2">
                                @php
                                    $colors = ['normal'=>'bg-green-100 text-green-700','warning'=>'bg-amber-100 text-amber-700','critical'=>'bg-orange-100 text-orange-700','breakdown'=>'bg-red-100 text-red-700'];
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colors[$asset->status] }}">
                                    {{ ucfirst($asset->status) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- DSS Rekomendasi --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Rekomendasi DSS</h2>
            <a href="{{ route('dss.index') }}" class="text-xs text-[#0E9E8E] hover:underline">Lihat semua →</a>
        </div>
        <div class="p-5 space-y-3">
            @forelse($rekomendasi as $rek)
            <div class="p-3 rounded-lg border-l-4 {{ $rek['level'] == 'high' ? 'border-red-500 bg-red-50' : ($rek['level'] == 'med' ? 'border-amber-500 bg-amber-50' : 'border-green-500 bg-green-50') }}">
                <div class="text-xs font-semibold text-gray-800">{{ $rek['title'] }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ $rek['desc'] }}</div>
            </div>
            @empty
            <p class="text-gray-400 text-sm text-center py-8">Tidak ada rekomendasi saat ini.</p>
            @endforelse
        </div>
    </div>

</div>

@endsection