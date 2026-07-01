<?php
$blade = <<<'BLADE'
@extends('layouts.app')

@section('title', 'Condition Monitoring')
@section('page-title', 'Condition Monitoring')
@section('page-sub', '— Data pengukuran & temuan visual')

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">{{ session('success') }}</div>
@endif

@if(session('import_errors') && count(session('import_errors')))
<div class="mb-4 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-sm">
    <p class="font-semibold mb-1">Beberapa baris dilewati:</p>
    <ul class="list-disc list-inside space-y-0.5 text-xs">
        @foreach(session('import_errors') as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- DASHBOARD CM — Ringkasan --}}
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 border border-gray-200">
        <div class="text-xs text-gray-400 mb-1">Total Records (12 bln)</div>
        <div class="text-2xl font-bold text-gray-800">{{ $totalRecords }}</div>
    </div>
    <div class="bg-white rounded-xl p-4 border border-green-200">
        <div class="text-xs text-green-600 mb-1">Normal</div>
        <div class="text-2xl font-bold text-green-500">{{ $goodCount }}</div>
    </div>
    <div class="bg-white rounded-xl p-4 border border-amber-200">
        <div class="text-xs text-amber-600 mb-1">Alarm</div>
        <div class="text-2xl font-bold text-amber-500">{{ $alarmCount }}</div>
    </div>
    <div class="bg-white rounded-xl p-4 border border-red-200">
        <div class="text-xs text-red-600 mb-1">Danger</div>
        <div class="text-2xl font-bold text-red-500">{{ $dangerCount }}</div>
    </div>
</div>

{{-- Baris 2: Donut per PT + Stacked Bar Trend --}}
<div class="grid grid-cols-5 gap-4 mb-6">
    <div class="col-span-2 bg-white rounded-xl border border-gray-200 p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Status per Perusahaan ({{ $latestMonthLabel }})</h3>
        <div id="donut-charts" class="grid grid-cols-2 md:grid-cols-3 gap-3">
            @foreach($cmsPerCompany as $item)
            <div class="text-center">
                <canvas id="donut-{{ $item['company']->id }}" height="80" width="80" class="inline-block"
                    data-good="{{ $item['good'] }}" data-alarm="{{ $item['alarm'] }}"
                    data-danger="{{ $item['danger'] }}" data-label="{{ $item['company']->code }}">
                </canvas>
                <p class="text-xs text-gray-500 mt-1">{{ $item['company']->code }}</p>
                <p class="text-xs text-gray-400">{{ $item['total'] }} record</p>
            </div>
            @endforeach
        </div>
    </div>
    <div class="col-span-3 bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Trend Status per Bulan</h3>
            <div class="flex gap-2">
                <select id="trend-range" onchange="updateTrend()" class="text-xs border border-gray-200 rounded px-2 py-1">
                    <option value="12">12 bulan</option>
                    <option value="6">6 bulan</option>
                    <option value="3">3 bulan</option>
                    <option value="custom">Custom</option>
                </select>
                <input type="month" id="trend-start" class="text-xs border border-gray-200 rounded px-2 py-1 hidden" onchange="updateTrend()">
                <input type="month" id="trend-end" class="text-xs border border-gray-200 rounded px-2 py-1 hidden" onchange="updateTrend()">
            </div>
        </div>
        <div style="height: 240px;"><canvas id="trendChart"></canvas></div>
    </div>
</div>

{{-- Baris 3: Top 10 Vibrasi --}}
<div class="bg-white rounded-xl border border-gray-200 mb-6">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700">Top 10 Vibrasi Tertinggi ({{ $latestMonthLabel }})</h3>
        <span class="text-xs text-gray-400">Max Vib (mm/s)</span>
    </div>
    <div class="px-6 py-4">
        @if($topVib->isNotEmpty())
        <div class="relative" style="height: {{ $topVib->count() * 40 + 40 }}px;"><canvas id="topVibChart"></canvas></div>
        @else
        <p class="text-center text-gray-400 text-sm py-4">Belum ada data pengukuran bulan ini.</p>
        @endif
    </div>
</div>

{{-- TAB NAVIGASI --}}
<div class="flex gap-2 mb-5">
    <button onclick="switchTab('measurements')" id="tab-measurements" class="px-4 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium transition">Pengukuran</button>
    <button onclick="switchTab('findings')" id="tab-findings" class="px-4 py-2 text-sm rounded-lg bg-white border border-gray-200 text-gray-600 font-medium transition">Temuan Visual</button>
    <div class="ml-auto flex gap-2">
        <a href="{{ route('cm.template') }}" class="border border-gray-200 text-gray-600 text-sm px-4 py-2 rounded-lg hover:bg-gray-50 transition">Download Template</a>
        <a href="{{ route('cm.import.form') }}" class="border border-gray-200 text-gray-600 text-sm px-4 py-2 rounded-lg hover:bg-gray-50 transition">Import Excel</a>
        <button onclick="document.getElementById('modal-add').classList.remove('hidden')" class="bg-[#0E9E8E] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#0a7a6d] transition">+ Tambah Data CM</button>
    </div>
</div>
BLADE;
echo $blade;
