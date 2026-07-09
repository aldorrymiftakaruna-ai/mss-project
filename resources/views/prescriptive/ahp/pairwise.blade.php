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
                                        data-ca="{{ $cA->label ?? $cA->name }}"
                                        data-cb="{{ $cB->label ?? $cB->name }}"
                                        class="pairwise-select text-xs border border-gray-200 rounded-lg px-2 py-1.5 w-20 text-center focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]/30 focus:border-[#0E9E8E]">
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
                                <td class="px-4 py-2.5 text-xs text-gray-500 keterangan-cell">
                                    @php
                                        $val = (float) $currentValue;
                                        $aLabel = $cA->label ?? $cA->name;
                                        $bLabel = $cB->label ?? $cB->name;

                                        // Mapping level kepentingan
                                        $levelMap = [1=>'sama penting', 2=>'sedikit lebih penting', 3=>'sedikit lebih penting', 4=>'lebih penting', 5=>'lebih penting', 6=>'jauh lebih penting', 7=>'jauh lebih penting', 8=>'mutlak/ekstrem lebih penting', 9=>'mutlak/ekstrem lebih penting'];

                                        if ($val == 1) {
                                            $keterangan = "{$aLabel} sama penting dengan {$bLabel}";
                                        } elseif ($val > 1) {
                                            $lvl = $levelMap[(int) round($val)] ?? 'lebih penting';
                                            $keterangan = "{$aLabel} {$lvl} dari {$bLabel}";
                                        } else {
                                            $lvl = $levelMap[(int) round(1 / $val)] ?? 'lebih penting';
                                            $keterangan = "{$bLabel} {$lvl} dari {$aLabel}";
                                        }
                                    @endphp
                                    {{ $keterangan }}
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

@push('scripts')
<script>
/**
 * Mapping keterangan skala Saaty untuk ditampilkan di kolom Keterangan.
 * Dipanggil dari JS on-change dropdown skala pairwise.
 */
(function() {
    // Mapping level skala (angka positif 1-9)
    const LEVELS = {
        1: 'sama penting',
        2: 'sedikit lebih penting',
        3: 'sedikit lebih penting',
        4: 'lebih penting',
        5: 'lebih penting',
        6: 'jauh lebih penting',
        7: 'jauh lebih penting',
        8: 'mutlak/ekstrem lebih penting',
        9: 'mutlak/ekstrem lebih penting',
    };

    // Tingkat untuk pecahan (nilai < 1) — ditampilkan dari sisi B
    const RECIPROCAL_LEVELS = {
        0.5: 'sedikit lebih penting',
        0.33: 'sedikit lebih penting',
        0.25: 'lebih penting',
        0.2: 'lebih penting',
        0.17: 'jauh lebih penting',
        0.14: 'jauh lebih penting',
        0.13: 'mutlak/ekstrem lebih penting',
        0.11: 'mutlak/ekstrem lebih penting',
    };

    /**
     * Menghasilkan teks keterangan berdasarkan nilai skala dan nama kriteria.
     *
     * @param {number} val - Nilai skala (1-9 atau pecahan 1/2-1/9)
     * @param {string} a - Nama kriteria A
     * @param {string} b - Nama kriteria B
     * @returns {string}
     */
    function getKeterangan(val, a, b) {
        var num = parseFloat(val);

        if (num === 1) {
            return a + ' sama penting dengan ' + b;
        }

        if (num > 1) {
            var level = LEVELS[Math.round(num)];
            if (!level) {
                // fallback untuk skala antara (misal 2.5 hasil kalau ada bug)
                if (num >= 2 && num <= 3) level = 'sedikit lebih penting';
                else if (num >= 4 && num <= 5) level = 'lebih penting';
                else if (num >= 6 && num <= 7) level = 'jauh lebih penting';
                else if (num >= 8) level = 'mutlak/ekstrem lebih penting';
                else level = 'lebih penting';
            }
            return a + ' ' + level + ' dari ' + b;
        }

        // Nilai < 1 → kebalikan: B lebih penting dari A
        var recipLevel = RECIPROCAL_LEVELS[num];
        if (!recipLevel) {
            // fallback berdasarkan range
            if (num >= 0.5) recipLevel = 'sedikit lebih penting';
            else if (num >= 0.25) recipLevel = 'lebih penting';
            else if (num >= 0.14) recipLevel = 'jauh lebih penting';
            else recipLevel = 'mutlak/ekstrem lebih penting';
        }
        return b + ' ' + recipLevel + ' dari ' + a;
    }

    // Attach event listener ke semua dropdown pairwise
    document.addEventListener('DOMContentLoaded', function() {
        var selects = document.querySelectorAll('.pairwise-select');
        selects.forEach(function(sel) {
            // Baca data atribut untuk nama kriteria
            var ca = sel.getAttribute('data-ca');
            var cb = sel.getAttribute('data-cb');

            // Dapatkan sel keterangan (saudara td setelah kriteria B)
            var tdKeterangan = sel.closest('tr').querySelector('.keterangan-cell');

            // Set initial text
            if (tdKeterangan) {
                tdKeterangan.textContent = getKeterangan(sel.value, ca, cb);
            }

            // Update on change
            sel.addEventListener('change', function() {
                if (tdKeterangan) {
                    tdKeterangan.textContent = getKeterangan(this.value, ca, cb);
                }
            });
        });
    });
})();
</script>
@endpush
