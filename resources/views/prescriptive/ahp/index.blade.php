@extends('layouts.app')

@section('title', 'AHP Prioritas')
@section('page-title', 'AHP Prioritas')
@section('page-sub', '— Analytic Hierarchy Process')

@push('header-actions')
<x-info-tooltip title="Bagaimana AHP + TOPSIS Bekerja?">
    <ul class="list-disc pl-4 space-y-1">
        <li><strong>AHP</strong> (Analytic Hierarchy Process) digunakan untuk menentukan bobot kriteria berdasarkan perbandingan berpasangan (pairwise comparison) yang diinput user pada tiap sesi.</li>
        <li>Setiap sesi berisi beberapa kriteria yang dibandingkan satu-lawan-satu menggunakan skala 1&ndash;9 (skala Saaty).</li>
        <li>Sistem menghitung <strong>Consistency Ratio (CR)</strong> untuk memastikan konsistensi penilaian; CR &lt; 0,1 dianggap &ldquo;Konsisten&rdquo;.</li>
        <li>Bobot kriteria hasil AHP kemudian dipakai sebagai input ke metode <strong>TOPSIS</strong> untuk meranking alternatif (equipment) berdasarkan kedekatannya terhadap solusi ideal.</li>
        <li>Data yang digunakan: input pairwise comparison dari user (session AHP) + data atribut equipment terkait kriteria yang dinilai.</li>
    </ul>
</x-info-tooltip>
@endpush

@section('content')
<div class="mb-5 flex items-center justify-between">
    <div>
        <p class="text-sm text-gray-600">Sesi perbandingan berpasangan untuk menentukan bobot kriteria keputusan.</p>
    </div>
    <a href="{{ route('ahp.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#0E9E8E] text-white rounded-lg hover:bg-[#0B8A7C] transition text-sm font-medium">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Sesi Baru
    </a>
</div>

@if(session('success'))
    <div class="mb-5 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{{ session('error') }}</div>
@endif

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    @if($sessions->isEmpty())
        <div class="p-10 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p class="text-gray-500 text-sm">Belum ada sesi AHP.</p>
            <a href="{{ route('ahp.create') }}" class="inline-block mt-3 text-sm text-[#0E9E8E] hover:underline">Buat sesi baru</a>
        </div>
    @else
        <table class="w-full text-sm">
            <thead>
                <tr class="text-xs text-gray-500 uppercase border-b border-gray-100 bg-gray-50">
                    <th class="text-left px-5 py-3">Nama Sesi</th>
                    <th class="text-left px-5 py-3">Kriteria</th>
                    <th class="text-left px-5 py-3">CR</th>
                    <th class="text-left px-5 py-3">Status</th>
                    <th class="text-left px-5 py-3">Dibuat</th>
                    <th class="text-right px-5 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($sessions as $s)
                <tr class="hover:bg-gray-50/50">
                    <td class="px-5 py-3.5 font-medium text-gray-800">{{ $s->name }}</td>
                    <td class="px-5 py-3.5 text-gray-500">{{ $s->criteria->count() }} kriteria</td>
                    <td class="px-5 py-3.5 font-mono text-xs {{ $s->is_final ? 'text-green-600' : ($s->consistency_ratio ? 'text-red-600' : 'text-gray-400') }}">
                        {{ $s->consistency_ratio ? number_format($s->consistency_ratio, 4) : '—' }}
                    </td>
                    <td class="px-5 py-3.5">
                        @if($s->is_final)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Konsisten ✓</span>
                        @elseif($s->consistency_ratio)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">Tidak Konsisten</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Baru</span>
                        @endif
                    </td>
                    <td class="px-5 py-3.5 text-xs text-gray-400">{{ $s->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('ahp.pairwise', $s->id) }}" class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition">Pairwise</a>
                            @if($s->criteria->isNotEmpty() && $s->consistency_ratio !== null)
                                <a href="{{ route('ahp.result', $s->id) }}" class="text-xs px-3 py-1.5 rounded-lg bg-[#0E9E8E]/10 text-[#0E9E8E] hover:bg-[#0E9E8E]/20 transition">Hasil</a>
                                @if($s->is_final)
                                <a href="{{ route('ahp.ranking', $s->id) }}" class="text-xs px-3 py-1.5 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition">Ranking</a>
                                @endif
                            @endif
                            <form method="POST" action="{{ route('ahp.destroy', $s->id) }}" onsubmit="return confirm('Hapus sesi ini?')" class="inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs px-3 py-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
