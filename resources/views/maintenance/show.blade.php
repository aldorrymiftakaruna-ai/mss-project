@extends('layouts.app')

@section('title', 'Detail Laporan — ' . ($maintenance->report_code ?? '#' . $maintenance->id))
@section('page-title', 'Detail Laporan')
@section('page-sub', '— ' . ($maintenance->report_code ?? '#' . $maintenance->id))

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
    {{ session('success') }}
</div>
@endif

<div class="flex items-center justify-between mb-5">
    <a href="{{ route('maintenance.index') }}"
       class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
        ← Kembali ke Laporan Maintenance
    </a>
    <div class="flex gap-2">
        <form action="{{ route('maintenance.destroy', $maintenance) }}" method="POST"
            onsubmit="return confirm('Hapus laporan ini?')">
            @csrf @method('DELETE')
            <button class="border border-red-200 text-red-500 text-sm px-4 py-2 rounded-lg hover:bg-red-50 transition">
                Hapus
            </button>
        </form>
    </div>
</div>

{{-- Informasi Utama --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <div class="flex items-start justify-between mb-5">
        <div>
            <h2 class="font-semibold text-gray-800 text-lg">
                Laporan #{{ $maintenance->report_code ?? $maintenance->id }}
            </h2>
            <p class="text-xs text-gray-400 mt-1">
                Dibuat {{ $maintenance->created_at->format('d M Y H:i') }}
                @if($maintenance->submitted_at)
                    &middot; Disubmit {{ $maintenance->submitted_at->format('d M Y H:i') }}
                @endif
            </p>
        </div>
        @php
            $colors = ['belum_selesai'=>'bg-red-100 text-red-700','selesai'=>'bg-green-100 text-green-700'];
            $sts = $maintenance->status ?? 'open';
        @endphp
        <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $colors[$sts] }}">
            {{ ucfirst(str_replace('_', ' ', $sts)) }}
        </span>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-x-8 gap-y-4 text-sm">
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Tanggal</p>
            <p class="text-gray-800">{{ $maintenance->tanggal ? $maintenance->tanggal->format('d M Y') : '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Shift</p>
            <p class="text-gray-800 capitalize">Shift {{ $maintenance->shift }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Jenis</p>
            <p class="text-gray-800 capitalize">{{ $maintenance->jenis ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Durasi Pekerjaan</p>
            <p class="text-gray-800">{{ $maintenance->work_duration_minutes ? $maintenance->work_duration_minutes . ' menit' : '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Downtime</p>
            <p class="text-gray-800">{{ $maintenance->downtime_minutes ? $maintenance->downtime_minutes . ' menit' : '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Lembur</p>
            <p class="text-gray-800">
                @if($maintenance->is_overtime)
                    Ya — {{ $maintenance->overtime_hours ?? '?' }} jam
                @else
                    Tidak
                @endif
            </p>
        </div>
    </div>
</div>

{{-- Equipment & Pelapor --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-3 text-sm">Equipment</h3>
        @if($maintenance->asset)
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-[#0E9E8E]/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-[#0E9E8E]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <a href="{{ route('assets.show', $maintenance->asset) }}" class="font-semibold text-gray-800 hover:text-[#0E9E8E]">
                    {{ $maintenance->asset->tag_no }}
                </a>
                <p class="text-sm text-gray-500">{{ $maintenance->asset->description }}</p>
            </div>
        </div>
        @else
        <p class="text-sm text-gray-400">Tidak ada data equipment.</p>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-3 text-sm">Dilaporkan Oleh</h3>
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div>
                <a href="{{ route('employees.show', $maintenance->reporter) }}" class="font-semibold text-gray-800 hover:text-[#0E9E8E]">
                    {{ $maintenance->reporter->name ?? '—' }}
                </a>
                <p class="text-sm text-gray-500">{{ $maintenance->reporter->employee_id ?? '' }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Deskripsi Masalah & Catatan/Feedback --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-3 text-sm">Deskripsi Masalah</h3>
        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $maintenance->deskripsi_masalah ?? '—' }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-3 text-sm">Catatan / Feedback</h3>
        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $maintenance->catatan ?? '—' }}</p>
    </div>
</div>

{{-- Root Cause & Foto --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-3 text-sm">Root Cause</h3>
        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $maintenance->root_cause ?? '—' }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-3 text-sm">Dokumentasi Foto</h3>
        @php $photos = $maintenance->photo_documentation_urls; @endphp
        @if(!empty($photos))
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            @foreach($photos as $url)
            <a href="{{ $url }}" target="_blank" class="block aspect-square rounded-lg overflow-hidden bg-gray-100">
                <img src="{{ $url }}" alt="Dokumentasi" class="w-full h-full object-cover hover:opacity-80 transition">
            </a>
            @endforeach
        </div>
        @else
        <p class="text-sm text-gray-400">Tidak ada foto.</p>
        @endif
    </div>
</div>

{{-- Downtime & Lembur (Edit) --}}
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="font-semibold text-gray-800">Data Downtime &amp; Lembur</h2>
        <button onclick="document.getElementById('modal-metrics').classList.remove('hidden')"
            class="text-sm text-[#0E9E8E] hover:underline">Edit</button>
    </div>
    <div class="grid grid-cols-2 gap-6 text-sm">
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Downtime</p>
            <p class="text-gray-800">{{ $maintenance->downtime_minutes ? $maintenance->downtime_minutes . ' menit' : '—' }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-400 mb-0.5">Lembur</p>
            <p class="text-gray-800">
                @if($maintenance->is_overtime)
                    Ya — {{ $maintenance->overtime_hours ?? '?' }} jam
                @else
                    Tidak lembur
                @endif
            </p>
        </div>
    </div>
</div>

{{-- Modal Edit Downtime & Lembur --}}
<div id="modal-metrics" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Edit Downtime &amp; Lembur</h3>
            <button onclick="document.getElementById('modal-metrics').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <form action="{{ route('maintenance.metrics.update', $maintenance) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Downtime (menit)</label>
                <input type="number" name="downtime_minutes" min="0" placeholder="30"
                    value="{{ $maintenance->downtime_minutes }}"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Lembur?</label>
                <select name="is_overtime" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm"
                    onchange="document.getElementById('modal-overtime-hours').classList.toggle('hidden', this.value !== '1')">
                    <option value="0" {{ $maintenance->is_overtime ? '' : 'selected' }}>Tidak</option>
                    <option value="1" {{ $maintenance->is_overtime ? 'selected' : '' }}>Ya</option>
                </select>
            </div>
            <div id="modal-overtime-hours" class="{{ $maintenance->is_overtime ? '' : 'hidden' }}">
                <label class="text-xs text-gray-500 mb-1 block">Jam Lembur</label>
                <input type="number" name="overtime_hours" min="0" max="24" step="0.5" placeholder="2.5"
                    value="{{ $maintenance->overtime_hours }}"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">
                    Simpan
                </button>
                <button type="button" onclick="document.getElementById('modal-metrics').classList.add('hidden')"
                    class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Manpower Logs --}}
<div class="bg-white rounded-xl border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">Log Tenaga Kerja</h2>
        <button onclick="document.getElementById('modal-manpower').classList.remove('hidden')"
            class="text-sm text-[#0E9E8E] hover:underline">+ Tambah Teknisi</button>
    </div>
    <div class="px-6 py-4 space-y-2">
        {{-- Pelapor otomatis dianggap mengerjakan --}}
        <div class="flex items-center gap-3 text-sm">
            <div class="w-8 h-8 rounded-full bg-[#0E9E8E]/10 flex items-center justify-center text-xs font-bold text-[#0E9E8E]">
                {{ strtoupper(substr($maintenance->reporter->name ?? '?', 0, 1)) }}
            </div>
            <a href="{{ route('employees.show', $maintenance->reporter) }}" class="font-medium text-gray-800 hover:text-[#0E9E8E]">
                {{ $maintenance->reporter->name ?? '—' }}
            </a>
            @if($maintenance->work_duration_minutes)
            <span class="text-xs text-gray-400">{{ $maintenance->work_duration_minutes }} mnt</span>
            @endif
            <span class="text-xs text-gray-400 italic">(pelapor)</span>
        </div>

        {{-- Teknisi tambahan dari manpower_logs --}}
        @php
            $extraLogs = $maintenance->manpowerLogs->where('employee_id', '!=', $maintenance->reported_by);
        @endphp
        @if($extraLogs->isNotEmpty())
            @foreach($extraLogs as $log)
            <div class="flex items-center gap-3 text-sm">
                <div class="w-8 h-8 rounded-full bg-[#0E9E8E]/10 flex items-center justify-center text-xs font-bold text-[#0E9E8E]">
                    {{ strtoupper(substr($log->employee->name ?? '?', 0, 1)) }}
                </div>
                <a href="{{ route('employees.show', $log->employee) }}" class="font-medium text-gray-800 hover:text-[#0E9E8E]">
                    {{ $log->employee->name ?? '—' }}
                </a>
                @if($log->durasi_menit)
                <span class="text-xs text-gray-400">{{ $log->durasi_menit }} mnt</span>
                @endif
            </div>
            @endforeach
        @else
        <p class="text-xs text-gray-400 italic">Belum ada teknisi tambahan.</p>
        @endif
    </div>
</div>

{{-- Modal Tambah Teknisi --}}
<div id="modal-manpower" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Tambah Teknisi</h3>
            <button onclick="document.getElementById('modal-manpower').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <form action="{{ route('maintenance.manpower.store', $maintenance) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Teknisi *</label>
                <select name="employee_id" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <option value="">Pilih teknisi...</option>
                    @foreach(\App\Models\Employee::where('is_active', true)->get() as $emp)
                    <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->role }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Durasi (menit)</label>
                <input type="number" name="durasi_menit" min="0" placeholder="120"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">
                    Tambahkan
                </button>
                <button type="button" onclick="document.getElementById('modal-manpower').classList.add('hidden')"
                    class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

{{-- AI Suggestion --}}
@if($maintenance->ai_suggestion_json)
<div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
    <div class="flex items-center gap-2 mb-4">
        <h2 class="font-semibold text-gray-800">Analisis AI</h2>
        @if($maintenance->ai_analyzed)
        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
            Confidence: {{ $maintenance->ai_confidence ?? '—' }}%
        </span>
        @endif
    </div>
    <pre class="text-sm text-gray-700 bg-gray-50 rounded-lg p-4 overflow-x-auto">{{ json_encode($maintenance->ai_suggestion_json, JSON_PRETTY_PRINT) }}</pre>
</div>
@endif

@endsection
