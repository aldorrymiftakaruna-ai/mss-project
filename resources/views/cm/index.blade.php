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

{{-- ============================================================
     TAB NAVIGASI + IMPORT BUTTON (paling atas, dekat judul halaman)
     ============================================================ --}}
<div class="flex items-center gap-2 mb-6">
    <button onclick="switchTab('dashboard')" id="tab-dashboard"
        class="px-4 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium transition">
        Dashboard CM
    </button>
    <button onclick="switchTab('findings')" id="tab-findings"
        class="px-4 py-2 text-sm rounded-lg bg-white border border-gray-200 text-gray-600 font-medium transition">
        Temuan Visual
    </button>
        <div class="ml-auto">
        <button onclick="document.getElementById('modal-add').classList.remove('hidden')" class="bg-[#0E9E8E] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#0a7a6d] transition">+ Tambah Data CM</button>
    </div>
</div>

{{-- ============================================================
     MODE: DASHBOARD CM — Ringkasan, Donut, Stacked Bar, Top Vib
     ============================================================ --}}
<div id="content-dashboard">
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

{{-- Baris 2: Donut per PT (kiri) + Stacked Bar Trend (kanan) --}}
<div class="grid grid-cols-5 gap-4 mb-6">
    {{-- Donut per PT --}}
    <div class="col-span-2 bg-white rounded-xl border border-gray-200 p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Status per Perusahaan ({{ $latestMonthLabel }})</h3>
        <div id="donut-charts" class="flex flex-wrap justify-center items-start gap-12 py-1">
            @foreach($cmsPerCompany as $item)
            <div class="text-center" style="width: 170px;">
                <canvas id="donut-{{ $item['company']->id }}" height="170" width="170"
                    class="block mx-auto"
                    data-good="{{ $item['good'] }}"
                    data-alarm="{{ $item['alarm'] }}"
                    data-danger="{{ $item['danger'] }}"
                    data-label="{{ $item['company']->code }}">
                </canvas>
                <p class="text-sm text-gray-600 font-medium mt-2">{{ $item['company']->code }}</p>
                <p class="text-xs text-gray-400">{{ $item['total'] }} record</p>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Stacked Bar Trend --}}
    <div class="col-span-3 bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Trend Status per Bulan</h3>
            <div class="flex gap-2">
                <select id="trend-range" onchange="updateTrend()"
                    class="text-xs border border-gray-200 rounded px-2 py-1">
                    <option value="12">12 bulan</option>
                    <option value="6">6 bulan</option>
                    <option value="3">3 bulan</option>
                    <option value="custom">Custom</option>
                </select>
                <input type="month" id="trend-start" class="text-xs border border-gray-200 rounded px-2 py-1 hidden"
                    onchange="updateTrend()">
                <input type="month" id="trend-end" class="text-xs border border-gray-200 rounded px-2 py-1 hidden"
                    onchange="updateTrend()">
            </div>
        </div>
        <div style="height: 240px;">
            <canvas id="trendChart"></canvas>
        </div>
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
        <div class="relative" style="height: {{ $topVib->count() * 40 + 40 }}px;">
            <canvas id="topVibChart"></canvas>
        </div>
        @else
        <p class="text-center text-gray-400 text-sm py-4">Belum ada data pengukuran bulan ini.</p>
        @endif
    </div>
</div>
</div>{{-- /#content-dashboard --}}

{{-- ============================================================
     MODE: TEMUAN VISUAL — daftar temuan berbasis card (klik → detail)
     ============================================================ --}}
{{-- Data finding untuk JS (modal detail & edit) --}}
@php
use Illuminate\Support\Facades\Storage;
$findingsJson = $findings->map(function($f) {
    return [
        'id'                => $f->id,
        'finding_code'      => $f->finding_code,
        'asset_tag'         => $f->asset->tag_no ?? '—',
        'asset_desc'        => $f->asset->description ?? '—',
        'company_code'      => $f->asset->company->code ?? '—',
        'asset_id'          => $f->asset_id,
        'tanggal'           => $f->tanggal->format('Y-m-d'),
        'tanggal_label'     => $f->tanggal->format('d M Y'),
        'kategori'          => $f->kategori ?? '',
        'severity'          => $f->severity ?? '',
        'status'            => $f->status ?? 'open',
        'analysis'          => $f->analysis ?? '',
        'action'            => $f->action ?? '',
        'pic'               => $f->pic ?? '',
        'date_action'       => $f->date_action ? $f->date_action->format('Y-m-d') : '',
        'date_action_label' => $f->date_action ? $f->date_action->format('d M Y') : '—',
        'remark'            => $f->remark ?? '',
        'foto_1'            => $f->foto_path   ? Storage::url($f->foto_path)   : null,
        'foto_2'            => $f->foto_path_2 ? Storage::url($f->foto_path_2) : null,
        'foto_3'            => $f->foto_path_3 ? Storage::url($f->foto_path_3) : null,
        'update_url'        => route('cm.findings.update', $f),
        'destroy_url'       => route('cm.findings.destroy', $f),
    ];
})->values();
@endphp
<script>
var findingsData = @json($findingsJson);
</script>

<div id="content-findings" class="hidden">
    {{-- Search Bar --}}
    <div class="mb-4">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="search-finding" placeholder="Cari ID Temuan, Equipment, Kategori, PIC, Analysis..." 
                class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]/30 focus:border-[#0E9E8E]">
        </div>
    </div>

    {{-- Tabel ala File Explorer --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
                        <th class="px-4 py-3 text-left w-10">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </th>
                        <th class="px-4 py-3 text-left">ID Temuan</th>
                        <th class="px-4 py-3 text-left">Equipment</th>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">Kategori</th>
                        <th class="px-4 py-3 text-center">Severity</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-left">PIC</th>
                    </tr>
                </thead>
                <tbody id="findings-tbody" class="divide-y divide-gray-50">
                    @forelse($findings as $f)
                    @php
                        $scColors = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-amber-100 text-amber-700','high'=>'bg-red-100 text-red-700'];
                        $stColors = ['open'=>'bg-red-100 text-red-700','closed'=>'bg-green-100 text-green-700'];
                    @endphp
                    <tr class="finding-row hover:bg-gray-50/80 cursor-pointer" data-id="{{ $f->id }}">
                        <td class="px-4 py-3 text-gray-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <a href="{{ route('cm.findings.show', $f) }}" class="font-mono text-xs font-semibold text-[#0E9E8E] hover:underline finding-code">
                                {{ $f->finding_code }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="finding-asset">
                                <p class="font-medium text-gray-800 text-xs">{{ $f->asset->tag_no ?? '—' }}</p>
                                <p class="text-[11px] text-gray-400 truncate max-w-[200px]">{{ $f->asset->description ?? '—' }} &middot; {{ $f->asset->company->code ?? '—' }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-600 text-xs finding-tanggal">{{ $f->tanggal->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-gray-600 text-xs finding-kategori">{{ $f->kategori ?: '—' }}</td>
                        <td class="px-4 py-3 text-center finding-severity">
                            @if($f->severity)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $scColors[$f->severity] ?? 'bg-gray-100 text-gray-500' }}">{{ ucfirst($f->severity) }}</span>
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center finding-status">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $stColors[$f->status] ?? 'bg-gray-100 text-gray-500' }}">{{ ucfirst($f->status ?? 'open') }}</span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs finding-pic">{{ $f->pic ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-gray-400 text-sm">Belum ada temuan visual.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ============================================================
     MODAL DETAIL TEMUAN VISUAL (read-only, ada tombol Edit & Hapus)
     ============================================================ --}}
<div id="modal-detail" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div>
                <h3 id="detail-asset-tag" class="font-semibold text-gray-800"></h3>
                <p id="detail-asset-desc" class="text-xs text-gray-400"></p>
            </div>
            <button onclick="closeDetail()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <div class="px-6 py-5 space-y-5">
            {{-- Badge status --}}
            <div class="flex gap-2 flex-wrap">
                <span id="detail-badge-severity" class="px-3 py-1 rounded-full text-xs font-medium"></span>
                <span id="detail-badge-status" class="px-3 py-1 rounded-full text-xs font-medium"></span>
                <span id="detail-kategori" class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600"></span>
                <span id="detail-tanggal" class="px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600"></span>
            </div>
            {{-- Analysis & Action --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-400 mb-1">Analysis</p>
                    <p id="detail-analysis" class="text-sm text-gray-700">—</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Action</p>
                    <p id="detail-action" class="text-sm text-gray-700">—</p>
                </div>
            </div>
            {{-- PIC & Date Action --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-400 mb-1">PIC</p>
                    <p id="detail-pic" class="text-sm text-gray-700">—</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 mb-1">Date Action</p>
                    <p id="detail-date-action" class="text-sm text-gray-700">—</p>
                </div>
            </div>
            {{-- Remark --}}
            <div id="detail-remark-wrap" class="hidden">
                <p class="text-xs text-gray-400 mb-1">Remark</p>
                <p id="detail-remark" class="text-sm text-gray-700"></p>
            </div>
            {{-- Foto --}}
            <div id="detail-foto-wrap" class="hidden">
                <p class="text-xs text-gray-400 mb-2">Foto Temuan</p>
                <div id="detail-foto-grid" class="flex gap-3 flex-wrap"></div>
            </div>
        </div>
        <div class="flex gap-3 px-6 py-4 border-t border-gray-100">
            <button onclick="openEdit(currentFindingId)" class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">Edit</button>
            <button onclick="confirmDelete(currentFindingId)" class="px-4 border border-red-200 text-red-500 py-2 rounded-lg text-sm hover:bg-red-50 transition">Hapus</button>
            <button onclick="closeDetail()" class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">Tutup</button>
        </div>
    </div>
</div>

{{-- Hidden form untuk hapus (method DELETE) --}}
<form id="form-delete-finding" method="POST" class="hidden">
    @csrf @method('DELETE')
</form>

{{-- ============================================================
     MODAL EDIT TEMUAN VISUAL
     ============================================================ --}}
<div id="modal-edit" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Edit Temuan Visual</h3>
            <button onclick="closeEdit()" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form id="form-edit-finding" method="POST" enctype="multipart/form-data" class="px-6 py-5 space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Equipment *</label>
                    <select name="asset_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">Pilih Equipment</option>
                        @foreach($assets as $asset)
                        <option value="{{ $asset->id }}">{{ $asset->tag_no }} — {{ $asset->description }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Tanggal *</label>
                    <input type="date" name="tanggal" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Kategori Finding</label>
                    <input type="text" name="kategori" placeholder="Contoh: Noise, Leak, Corosion"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <p class="text-[10px] text-gray-400 mt-1">Bisa isi lebih dari satu, pisahkan dengan koma.</p>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Severity</label>
                    <select name="severity" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">— Tidak diisi —</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">PIC</label>
                    <select name="pic" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">Pilih PIC</option>
                        <option value="Mechanic">Mechanic</option>
                        <option value="Electric">Electric</option>
                        <option value="Production">Production</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Status</label>
                    <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="open">Open</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Analysis</label>
                    <textarea name="analysis" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Action</label>
                    <textarea name="action" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>
            </div>

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Date Action</label>
                <input type="date" name="date_action" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Remark</label>
                <textarea name="remark" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>

            {{-- Upload foto (opsional, hanya timpa jika diisi) --}}
            <div class="border border-gray-100 rounded-lg p-3 space-y-2">
                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Foto (opsional — kosongkan jika tidak diganti)</p>
                <div id="edit-foto-preview" class="flex gap-3 flex-wrap mb-2"></div>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="text-xs text-gray-400 block mb-1">Foto 1</label>
                        <input type="file" name="foto_1" accept="image/*" class="w-full text-xs border border-gray-200 rounded px-2 py-1.5">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 block mb-1">Foto 2</label>
                        <input type="file" name="foto_2" accept="image/*" class="w-full text-xs border border-gray-200 rounded px-2 py-1.5">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 block mb-1">Foto 3</label>
                        <input type="file" name="foto_3" accept="image/*" class="w-full text-xs border border-gray-200 rounded px-2 py-1.5">
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">Simpan Perubahan</button>
                <button type="button" onclick="closeEdit()" class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">Batal</button>
            </div>
        </form>
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
                <div id="import-measurement" class="flex gap-2 mb-4">
            <a href="{{ route('cm.template') }}"
               class="flex-1 text-center border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50 transition">
                Download Template
            </a>
            <a href="{{ route('cm.import.form') }}"
               class="flex-1 text-center border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50 transition">
                Import Excel
            </a>
        </div>
                <div id="import-finding" class="hidden flex gap-2 mb-4">
            <a href="{{ route('cm.findings.template') }}"
               class="flex-1 text-center border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50 transition">
                Download Template
            </a>
            <a href="{{ route('cm.findings.import.form') }}"
               class="flex-1 text-center border border-gray-200 text-gray-600 text-sm font-medium px-4 py-2 rounded-lg hover:bg-gray-50 transition">
                Import Excel
            </a>
        </div>
        <form action="{{ route('cm.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
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
                        <div><label class="text-xs text-gray-400">DE Temp (&deg;C)</label><input type="number" step="0.01" name="driver_de_temp" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Vib V</label><input type="number" step="0.01" name="driver_nde_vib_v" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Vib H</label><input type="number" step="0.01" name="driver_nde_vib_h" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Vib A</label><input type="number" step="0.01" name="driver_nde_vib_a" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE CF+</label><input type="number" step="0.01" name="driver_nde_cf" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Temp (&deg;C)</label><input type="number" step="0.01" name="driver_nde_temp" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
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
                        <div><label class="text-xs text-gray-400">DE Temp (&deg;C)</label><input type="number" step="0.01" name="driven_de_temp" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Vib V</label><input type="number" step="0.01" name="driven_nde_vib_v" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Vib H</label><input type="number" step="0.01" name="driven_nde_vib_h" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Vib A</label><input type="number" step="0.01" name="driven_nde_vib_a" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE CF+</label><input type="number" step="0.01" name="driven_nde_cf" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                        <div><label class="text-xs text-gray-400">NDE Temp (&deg;C)</label><input type="number" step="0.01" name="driven_nde_temp" class="w-full border border-gray-200 rounded px-2 py-1.5 text-xs"></div>
                    </div>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Catatan</label>
                    <textarea name="catatan" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>
            </div>
            <div id="fields-finding" class="space-y-4 hidden">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Kategori Finding</label>
                        <input type="text" name="kategori" placeholder="Contoh: Noise, Leak, Corosion"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <p class="text-[10px] text-gray-400 mt-1">Bisa isi lebih dari satu, pisahkan dengan koma.</p>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Severity</label>
                        <select name="severity" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                            <option value="">— Tidak diisi —</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">PIC</label>
                        <select name="pic" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                            <option value="">Pilih PIC</option>
                            <option value="Mechanic">Mechanic</option>
                            <option value="Electric">Electric</option>
                            <option value="Production">Production</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Status</label>
                        <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Analysis</label>
                        <textarea name="analysis" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Action</label>
                        <textarea name="action" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></textarea>
                    </div>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Date Action</label>
                    <input type="date" name="date_action" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Remark</label>
                    <textarea name="remark" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"></textarea>
                </div>
                <div class="border border-gray-100 rounded-lg p-3">
                    <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Foto (maks. 3, opsional)</p>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="text-xs text-gray-400 block mb-1">Foto 1</label>
                            <input type="file" name="foto_1" accept="image/*" class="w-full text-xs border border-gray-200 rounded px-2 py-1.5">
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 block mb-1">Foto 2</label>
                            <input type="file" name="foto_2" accept="image/*" class="w-full text-xs border border-gray-200 rounded px-2 py-1.5">
                        </div>
                        <div>
                            <label class="text-xs text-gray-400 block mb-1">Foto 3</label>
                            <input type="file" name="foto_3" accept="image/*" class="w-full text-xs border border-gray-200 rounded px-2 py-1.5">
                        </div>
                    </div>
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
    var canvasEl = document.getElementById('topVibChart');
    if (!canvasEl) { return; } // canvas tidak dirender Blade kalau topVib kosong
    var ctx = canvasEl.getContext('2d');
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
    document.querySelectorAll('canvas[id^="donut-"]').forEach(function(canvas) {
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
    document.getElementById('content-dashboard').classList.toggle('hidden', tab !== 'dashboard');
    document.getElementById('content-findings').classList.toggle('hidden', tab !== 'findings');
    document.getElementById('tab-dashboard').className = tab === 'dashboard' ? 'px-4 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium transition' : 'px-4 py-2 text-sm rounded-lg bg-white border border-gray-200 text-gray-600 font-medium transition';
    document.getElementById('tab-findings').className = tab === 'findings' ? 'px-4 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium transition' : 'px-4 py-2 text-sm rounded-lg bg-white border border-gray-200 text-gray-600 font-medium transition';
}

function switchType(type) {
    document.getElementById('input-type').value = type;
    document.getElementById('fields-measurement').classList.toggle('hidden', type !== 'measurement');
    document.getElementById('fields-finding').classList.toggle('hidden', type !== 'finding');
    document.getElementById('import-measurement').classList.toggle('hidden', type !== 'measurement');
    document.getElementById('import-finding').classList.toggle('hidden', type !== 'finding');
    document.getElementById('type-measurement').className = type === 'measurement' ? 'flex-1 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium' : 'flex-1 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 font-medium';
    document.getElementById('type-finding').className = type === 'finding' ? 'flex-1 py-2 text-sm rounded-lg bg-[#0E9E8E] text-white font-medium' : 'flex-1 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 font-medium';
}

async function updateTrend() {
    if (!trendChart) {
        console.error('updateTrend dibatalkan: trendChart belum siap (kemungkinan initTrendChart gagal sebelumnya, cek log "Trend chart gagal render").');
        return;
    }
    var range = document.getElementById('trend-range').value;
    var startInput = document.getElementById('trend-start');
    var endInput = document.getElementById('trend-end');
    if (range === 'custom') {
        startInput.classList.remove('hidden'); endInput.classList.remove('hidden');
        if (!startInput.value || !endInput.value) return;
    } else { startInput.classList.add('hidden'); endInput.classList.add('hidden'); }
    var params = new URLSearchParams({ range: range });
    if (range === 'custom') { params.set('start', startInput.value); params.set('end', endInput.value); }
    var url = '{{ route("cm.trend-data") }}?' + params.toString();
    console.log('updateTrend: fetching', url);
    try {
        var res = await fetch(url);
        if (!res.ok) {
            console.error('updateTrend: response status', res.status, res.statusText);
            alert('Gagal mengambil data trend (status ' + res.status + ')');
            return;
        }
        var data = await res.json();
        console.log('updateTrend: response data', data);
        if (data.error) { alert(data.error); return; }
        if (!data.labels || !data.good || !data.alarm || !data.danger) {
            console.error('updateTrend: format response tidak sesuai ekspektasi (butuh labels, good, alarm, danger)', data);
            return;
        }
        trendChart.data.labels = data.labels;
        trendChart.data.datasets[0].data = data.good;
        trendChart.data.datasets[1].data = data.alarm;
        trendChart.data.datasets[2].data = data.danger;
        trendChart.update();
    } catch(e) { console.error('Gagal update trend:', e); }
}


// ── Finding Detail & Edit Modal ──────────────────────────────
var currentFindingId = null;

var scBadge = {
    low:    'bg-green-100 text-green-700',
    medium: 'bg-amber-100 text-amber-700',
    high:   'bg-red-100 text-red-700'
};
var stBadge = {
    open:   'bg-red-100 text-red-700',
    closed: 'bg-green-100 text-green-700'
};

function findingById(id) {
    return findingsData.find(function(f) { return f.id == id; });
}

function openDetail(id) {
    var f = findingById(id);
    if (!f) return;
    currentFindingId = id;

    document.getElementById('detail-asset-tag').textContent  = f.asset_tag;
    document.getElementById('detail-asset-desc').textContent = f.asset_desc + ' · ' + f.company_code;
    document.getElementById('detail-analysis').textContent   = f.analysis  || '—';
    document.getElementById('detail-action').textContent     = f.action    || '—';
    document.getElementById('detail-pic').textContent        = f.pic || '—';
    document.getElementById('detail-date-action').textContent = f.date_action_label;

    var sev = document.getElementById('detail-badge-severity');
    if (f.severity) {
        sev.textContent  = f.severity.charAt(0).toUpperCase() + f.severity.slice(1);
        sev.className    = 'px-3 py-1 rounded-full text-xs font-medium ' + (scBadge[f.severity] || 'bg-gray-100 text-gray-600');
        sev.classList.remove('hidden');
    } else {
        sev.classList.add('hidden');
    }

    var sta = document.getElementById('detail-badge-status');
    var status = f.status || 'open';
    sta.textContent  = status.charAt(0).toUpperCase() + status.slice(1);
    sta.className    = 'px-3 py-1 rounded-full text-xs font-medium ' + (stBadge[status] || 'bg-gray-100 text-gray-600');

    document.getElementById('detail-kategori').textContent = f.kategori || '—';
    document.getElementById('detail-tanggal').textContent  = f.tanggal_label;

    // Remark
    var remarkWrap = document.getElementById('detail-remark-wrap');
    if (f.remark) {
        document.getElementById('detail-remark').textContent = f.remark;
        remarkWrap.classList.remove('hidden');
    } else {
        remarkWrap.classList.add('hidden');
    }

    // Foto
    var fotoWrap = document.getElementById('detail-foto-wrap');
    var fotoGrid = document.getElementById('detail-foto-grid');
    fotoGrid.innerHTML = '';
    var fotos = [f.foto_1, f.foto_2, f.foto_3].filter(Boolean);
    if (fotos.length > 0) {
        fotos.forEach(function(src) {
            var img = document.createElement('img');
            img.src = src;
            img.className = 'h-28 w-28 object-cover rounded-lg border border-gray-200 cursor-pointer';
            img.onclick = function() { window.open(src, '_blank'); };
            fotoGrid.appendChild(img);
        });
        fotoWrap.classList.remove('hidden');
    } else {
        fotoWrap.classList.add('hidden');
    }

    document.getElementById('modal-detail').classList.remove('hidden');
}

function closeDetail() {
    document.getElementById('modal-detail').classList.add('hidden');
    currentFindingId = null;
}

function openEdit(id) {
    var f = findingById(id);
    if (!f) return;
    closeDetail();

    var form = document.getElementById('form-edit-finding');
    form.action = f.update_url;

    // Isi field
    form.querySelector('[name="asset_id"]').value    = f.asset_id;
    form.querySelector('[name="tanggal"]').value      = f.tanggal;
    form.querySelector('[name="kategori"]').value     = f.kategori;
    form.querySelector('[name="severity"]').value     = f.severity || '';
    form.querySelector('[name="pic"]').value          = f.pic || '';
    form.querySelector('[name="status"]').value        = f.status || 'open';
    form.querySelector('[name="analysis"]').value     = f.analysis;
    form.querySelector('[name="action"]').value       = f.action;
    form.querySelector('[name="date_action"]').value  = f.date_action || '';
    form.querySelector('[name="remark"]').value       = f.remark;

    // Preview foto existing
    var preview = document.getElementById('edit-foto-preview');
    preview.innerHTML = '';
    var fotos = [f.foto_1, f.foto_2, f.foto_3].filter(Boolean);
    fotos.forEach(function(src, i) {
        var wrap = document.createElement('div');
        wrap.className = 'relative';
        var img = document.createElement('img');
        img.src = src;
        img.className = 'h-20 w-20 object-cover rounded-lg border border-gray-200';
        var lbl = document.createElement('p');
        lbl.textContent = 'Foto ' + (i + 1);
        lbl.className = 'text-xs text-gray-400 text-center mt-1';
        wrap.appendChild(img);
        wrap.appendChild(lbl);
        preview.appendChild(wrap);
    });

    document.getElementById('modal-edit').classList.remove('hidden');
}

function closeEdit() {
    document.getElementById('modal-edit').classList.add('hidden');
}

function confirmDelete(id) {
    var f = findingById(id);
    if (!f) return;
    if (!confirm('Hapus temuan ' + f.asset_tag + ' (' + f.tanggal_label + ')? Tindakan ini tidak bisa dibatalkan.')) return;
    var form = document.getElementById('form-delete-finding');
    form.action = f.destroy_url;
        form.submit();
}

// ── Search Temuan (filter tabel real-time) ──────────────────
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('search-finding');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var q = this.value.toLowerCase().trim();
            var rows = document.querySelectorAll('#findings-tbody .finding-row');
            rows.forEach(function(row) {
                var code     = (row.querySelector('.finding-code')?.textContent || '').toLowerCase();
                var asset    = (row.querySelector('.finding-asset')?.textContent || '').toLowerCase();
                var tanggal  = (row.querySelector('.finding-tanggal')?.textContent || '').toLowerCase();
                var kategori = (row.querySelector('.finding-kategori')?.textContent || '').toLowerCase();
                var severity = (row.querySelector('.finding-severity')?.textContent || '').toLowerCase();
                var status   = (row.querySelector('.finding-status')?.textContent || '').toLowerCase();
                var pic      = (row.querySelector('.finding-pic')?.textContent || '').toLowerCase();
                var match = code.includes(q) || asset.includes(q) || tanggal.includes(q) || kategori.includes(q) || severity.includes(q) || status.includes(q) || pic.includes(q);
                row.style.display = match ? '' : 'none';
            });
        });
    }

    try { initDonutCharts(); } catch (e) { console.error('Donut chart gagal render:', e); }
    try { initTrendChart(); } catch (e) { console.error('Trend chart gagal render:', e); }
    try { initTopVibChart(); } catch (e) { console.error('Top Vib chart gagal render:', e); }
});

// ── Klik baris → buka detail (via modal) ────────────────────
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('#findings-tbody')?.addEventListener('click', function(e) {
        var row = e.target.closest('.finding-row');
        if (!row) return;
        // Kalo yang diklik link, jangan di-intercept
        if (e.target.closest('a')) return;
        var id = row.dataset.id;
        if (id) window.location.href = '{{ route("cm.index") }}/findings/' + id;
    });
});
</script>

@endsection