<div
    class="relative inline-flex items-center info-tooltip"
    data-title="{{ $title ?? 'Informasi' }}"
>
    {{-- Ikon ? --}}
    <button type="button" class="flex items-center justify-center w-5 h-5 rounded-full border border-gray-300 text-gray-400 hover:text-gray-600 hover:border-gray-400 hover:bg-gray-50 transition cursor-pointer flex-shrink-0 info-tooltip-btn" aria-label="Info">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M12 17.25h.007v.008H12v-.008z" />
        </svg>
    </button>

    {{-- Tooltip --}}
    <div class="absolute left-1/2 -translate-x-1/2 top-full mt-3 z-50 hidden info-tooltip-body">
        <div class="bg-white border border-gray-200 rounded-xl shadow-lg p-4 w-80 text-left">
            <div class="text-xs font-semibold text-gray-800 info-tooltip-title">{{ $title ?? 'Informasi' }}</div>
            <div class="text-xs text-gray-600 mt-2 space-y-1.5 leading-relaxed info-tooltip-content">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
