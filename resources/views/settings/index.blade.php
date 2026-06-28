@extends('layouts.app')

@section('title', 'Pengaturan Sistem')
@section('page-title', 'Pengaturan')
@section('page-sub', '— Threshold status equipment')

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
    {{ session('success') }}
</div>
@endif

<form action="{{ route('settings.update') }}" method="POST">
    @csrf
    @method('PUT')

    {{-- ── Vibration Thresholds ─────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Threshold Vibrasi (ISO 10816-3)</h2>
            <p class="text-xs text-gray-400 mt-0.5">
                Nilai dalam mm/s (velocity RMS). Threshold dibagi per class motor berdasarkan kW.
            </p>
        </div>
        <div class="p-6 space-y-6">

            @php
                $classes = [
                    ['label' => 'Class I — Motor ≤ 15 kW',    'alarm' => 'vib_class1_alarm', 'danger' => 'vib_class1_danger'],
                    ['label' => 'Class II — Motor 15–75 kW',  'alarm' => 'vib_class2_alarm', 'danger' => 'vib_class2_danger'],
                    ['label' => 'Class III — Motor 75–300 kW','alarm' => 'vib_class3_alarm', 'danger' => 'vib_class3_danger'],
                    ['label' => 'Class IV — Motor > 300 kW',  'alarm' => 'vib_class4_alarm', 'danger' => 'vib_class4_danger'],
                ];
                // Flatten settings ke key=>value untuk mudah akses
                $flat = $settings->flatten()->keyBy('key');
            @endphp

            @foreach($classes as $cls)
            <div>
                <p class="text-sm font-medium text-gray-700 mb-3">{{ $cls['label'] }}</p>
                <div class="grid grid-cols-2 gap-4 max-w-md">
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">
                            Alarm <span class="text-amber-600">(kuning)</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number" step="0.1" min="0"
                                name="settings[{{ $cls['alarm'] }}]"
                                value="{{ $flat[$cls['alarm']]->value ?? '' }}"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                            <span class="text-xs text-gray-400 whitespace-nowrap">mm/s</span>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">
                            Danger <span class="text-red-600">(merah)</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number" step="0.1" min="0"
                                name="settings[{{ $cls['danger'] }}]"
                                value="{{ $flat[$cls['danger']]->value ?? '' }}"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                            <span class="text-xs text-gray-400 whitespace-nowrap">mm/s</span>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

        </div>
    </div>

    {{-- ── Temperature Threshold ────────────────────────────────── --}}
    <div class="bg-white rounded-xl border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Threshold Temperature Bearing</h2>
            <p class="text-xs text-gray-400 mt-0.5">
                Jika temperature bearing ≥ nilai ini, status langsung Danger (merah).
            </p>
        </div>
        <div class="p-6">
            <div class="max-w-xs">
                <label class="text-xs text-gray-500 mb-1 block">
                    Danger Temperature <span class="text-red-600">(merah)</span>
                </label>
                <div class="flex items-center gap-2">
                    <input type="number" step="1" min="0"
                        name="settings[temp_danger]"
                        value="{{ $flat['temp_danger']->value ?? 82 }}"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    <span class="text-xs text-gray-400 whitespace-nowrap">°C</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Save button ──────────────────────────────────────────── --}}
    <div class="flex justify-end">
        <button type="submit"
            class="bg-[#0E9E8E] text-white px-6 py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">
            Simpan Pengaturan
        </button>
    </div>

</form>

@endsection