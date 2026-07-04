@extends('layouts.app')

@section('title', 'Pairwise - ' . $session->name)
@section('page-title', 'Pairwise: ' . $session->name)
@section('page-sub', '— Isi perbandingan berpasangan skala Saaty 1-9')

@section('content')
@if(session('success'))
    <div class="mb-5 px-4 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-5 px-4 py-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{{ session('error') }}</div>
@endif

<div class="mb-5 flex items-center justify-between">
    <div class="text-xs text-gray-500">
        <span class="font-medium text-gray-700">{{ $criteria->count() }} kriteria</span> &middot;
        Skala: 1 (sama penting) s.d 9 (mutlak lebih penting)
    </div>
    <a href="{{ route('ahp.index') }}" class="text-xs px-3 py-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition">&larr; Kembali</a>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <form method="POST" action="{{ route('ahp.storePairwise', $session->id) }}">
        @csrf

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase border-b border-gray-100 bg-gray-50">
                        <th class="text-left px-4 py-3 w-1/4">Kriteria A</th>
                        <th class="text-center px-4 py-3 w-1/12">Skala</th>
                        <th class="text-left px-4 py-3 w-1/4">Kriteria B</th>
                        <th class="text-left px-4 py-3 text-gray-400 font-normal">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @php
                        $count = $criteria->count();
                        $list = $criteria->values();
                    @endphp
                    @for($i = 0; $i < $count; $i++)
                        @for($j = $i + 1; $j < $count; $j++)
                            @php
                                $cA = $list[$i];
                                $cB = $list[$j];
                                $key = $cA->id . '_' . $cB->id;
                                $currentValue = $matrix[$i][$j] ?? 1;
                            @endphp
                            <tr>
                                <td class="px-4 py-2.5 font-medium text-gray-800">{{ $cA->label ?? $cA->name }}</td>
                                <td class="px-4 py-2.5 text-center">
                                    <select name="pairwise[{{ $key }}]"
                                        class="text-xs border border-gray-200 rounded-lg px-2 py-1.5 w-20 text-center focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]/30 focus:border-[#0E9E8E]">
                                        <option value="9" {{ $currentValue == 9 ? 'selected' : '' }}>9</option>
                                        <option value="8" {{ $currentValue == 8 ? 'selected' : '' }}>8</option>
                                        <option value="7" {{ $currentValue == 7 ? 'selected' : '' }}>7</option>
                                        <option value="6" {{ $currentValue == 6 ? 'selected' : '' }}>6</option>
                                        <option value="5" {{ $currentValue == 5 ? 'selected' : '' }}>5</option>
                                        <option value="4" {{ $currentValue == 4 ? 'selected' : '' }}>4</option>
                                        <option value="3" {{ $currentValue == 3 ? 'selected' : '' }}>3</option>
                                        <option value="2" {{ $currentValue == 2 ? 'selected' : '' }}>2</option>
                                        <option value="1" {{ $currentValue == 1 ? 'selected' : '' }} selected>1</option>
                                        <option value="0.5" {{ $currentValue == 0.5 ? 'selected' : '' }}>1/2</option>
                                        <option value="0.33" {{ $currentValue == 1/3 ? 'selected' : '' }}>1/3</option>
                                        <option value="0.25" {{ $currentValue == 0.25 ? 'selected' : '' }}>1/4</option>
                                        <option value="0.2" {{ $currentValue == 0.2 ? 'selected' : '' }}>1/5</option>
                                        <option value="0.17" {{ $currentValue == 1/6 ? 'selected' : '' }}>1/6</option>
                                        <option value="0.14" {{ $currentValue == 1/7 ? 'selected' : '' }}>1/7</option>
                                        <option value="0.13" {{ $currentValue == 1/8 ? 'selected' : '' }}>1/8</option>
                                        <option value="0.11" {{ $currentValue == 1/9 ? 'selected' : '' }}>1/9</option>
                                    </select>
                                </td>
                                <td class="px-4 py-2.5 font-medium text-gray-800">{{ $cB->label ?? $cB->name }}</td>
                                <td class="px-4 py-2.5 text-xs text-gray-400">
                                    @php
                                        $val = (float) $currentValue;
                                        $aLabel = $cA->label ?? $cA->name;
                                        $bLabel = $cB->label ?? $cB->name;
                                    @endphp
                                    @if($val == 1)
                                        {{ $aLabel }} sama penting dengan {{ $bLabel }}
                                    @elseif($val > 1)
                                        {{ $aLabel }} {{ \App\Services\Prescriptive\AhpService::SAATY_SCALE[(int)$val] ?? 'lebih penting' }} dari {{ $bLabel }}
                                    @else
                                        {{ $bLabel }} {{ \App\Services\Prescriptive\AhpService::SAATY_SCALE[(int)(1/$val)] ?? 'lebih penting' }} dari {{ $aLabel }}
                                    @endif
                                </td>
                            </tr>
                        @endfor
                    @endfor
                </tbody>
            </table>
        </div>

        <div class="px-4 py-4 border-t border-gray-100 flex items-center justify-between bg-gray-50">
            <p class="text-xs text-gray-400">Nilai reciprocal akan dihitung otomatis.</p>
            <div class="flex items-center gap-3">
                <a href="{{ route('ahp.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition">Batal</a>
                <button type="submit" class="px-5 py-2 bg-[#0E9E8E] text-white rounded-lg hover:bg-[#0B8A7C] transition text-sm font-medium">
                    Simpan & Hitung
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
