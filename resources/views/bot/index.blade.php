@extends('layouts.app')

@section('title', 'Panel Bot Telegram')
@section('page-title', 'Panel Bot Telegram')
@section('page-sub', 'Manajemen koneksi bot Telegram dan daftar teknisi terhubung')

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
    {{ session('error') }}
</div>
@endif

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-[#0E9E8E]/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-[#0E9E8E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500 truncate">Status Bot</p>
                <p class="text-sm font-bold @if($stats['bot_status'] === 'terkonfigurasi') text-green-600 @else text-amber-600 @endif">
                    @if($stats['bot_status'] === 'terkonfigurasi')
                        Online
                    @else
                        Belum Konfigurasi
                    @endif
                </p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500 truncate">Teknisi Aktif</p>
                <p class="text-sm font-bold text-gray-900">{{ $stats['teknisi_aktif'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-violet-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500 truncate">Terhubung Bot</p>
                <p class="text-sm font-bold text-gray-900">{{ $stats['terhubung_bot'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500 truncate">Laporan Via Bot</p>
                <p class="text-sm font-bold text-gray-900">{{ $stats['laporan_via_bot'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500 truncate">Hari Ini</p>
                <p class="text-sm font-bold text-gray-900">{{ $stats['laporan_hari_ini'] }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-rose-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-gray-500 truncate">Unknown Assets</p>
                <p class="text-sm font-bold text-rose-600">{{ $stats['unknown_assets'] }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Tab Navigation --}}
<div class="mb-6" x-data="{ activeTab: 'status' }">
    <div class="border-b border-gray-200 mb-4">
        <nav class="flex gap-6 -mb-px overflow-x-auto">
            <button onclick="switchTab('status')" id="tab-status-btn" class="tab-btn whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors border-[#0E9E8E] text-[#0E9E8E]" data-tab="status">
                Status & Koneksi
            </button>
            <button onclick="switchTab('pendaftaran')" id="tab-pendaftaran-btn" class="tab-btn whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors border-transparent text-gray-500 hover:text-gray-700" data-tab="pendaftaran">
                Pendaftaran
                @if($pendaftaran->total() > 0)
                    <span class="ml-1.5 px-1.5 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-700 rounded-full">{{ $pendaftaran->total() }}</span>
                @endif
            </button>
            <button onclick="switchTab('teknisi')" id="tab-teknisi-btn" class="tab-btn whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors border-transparent text-gray-500 hover:text-gray-700" data-tab="teknisi">
                Teknisi Aktif Bot
                @if($teknisiList->count() > 0)
                    <span class="ml-1.5 px-1.5 py-0.5 text-[10px] font-bold bg-[#0E9E8E]/10 text-[#0E9E8E] rounded-full">{{ $teknisiList->count() }}</span>
                @endif
            </button>
            <button onclick="switchTab('unknown')" id="tab-unknown-btn" class="tab-btn whitespace-nowrap pb-3 px-1 border-b-2 text-sm font-medium transition-colors border-transparent text-gray-500 hover:text-gray-700" data-tab="unknown">
                Unknown Assets
                @if($unknownAssets->total() > 0)
                    <span class="ml-1.5 px-1.5 py-0.5 text-[10px] font-bold bg-rose-100 text-rose-700 rounded-full">{{ $unknownAssets->total() }}</span>
                @endif
            </button>
        </nav>
    </div>
{{-- Tab 1: Status & Koneksi --}}
<div id="tab-status" class="tab-content">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-medium text-gray-900">Konfigurasi Bot Token</h3>
            </div>
            <form method="POST" action="{{ route('bot.settings') }}" class="p-5 space-y-4">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700">Bot Token</label>
                    <input type="password" name="token" id="bot-token-input"
                           value="{{ config('telegram.bot_token') }}"
                           placeholder="Isi TELEGRAM_BOT_TOKEN di .env atau masukkan di sini"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700">Status Bot</label>
                    <select name="status" id="bot-status-select"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]">
                        <option value="active" {{ config('telegram.bot_token') ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ !config('telegram.bot_token') ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-gray-700">Auto Approve</label>
                        <select name="auto_approve"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]">
                            <option value="1">Ya</option>
                            <option value="0" selected>Tidak</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-sm font-medium text-gray-700">Max Item</label>
                        <input type="number" name="max_item" value="10" min="1" max="20"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]">
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-sm font-medium text-gray-700">Channel Notifikasi (opsional)</label>
                    <input type="text" name="notif_channel" placeholder="@channel_username atau chat_id"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#0E9E8E]">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="px-4 py-2 bg-[#0E9E8E] hover:bg-[#0a7a6d] text-white text-sm font-medium rounded-lg transition-colors">
                        Simpan
                    </button>
                    <button type="button" onclick="testConnection()"
                            class="px-4 py-2 border border-green-500 hover:bg-green-50 text-green-600 text-sm font-medium rounded-lg transition-colors">
                        Test Koneksi
                    </button>
                </div>
            </form>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-900">Webhook</h3>
                </div>
                <div class="p-5 space-y-3">
                    <div class="space-y-1">
                        <label class="block text-xs text-gray-500">Webhook URL</label>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly
                                   value="{{ route('telegram.webhook') }}"
                                   class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-xs font-mono text-gray-600 bg-gray-50 focus:outline-none">
                            <button onclick="copyWebhookUrl()"
                                    class="px-3 py-2 text-xs border border-gray-300 hover:bg-gray-50 text-gray-600 font-medium rounded-lg transition-colors">
                                Salin
                            </button>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="setWebhook()"
                                class="px-4 py-2 bg-[#0E9E8E] hover:bg-[#0a7a6d] text-white text-sm font-medium rounded-lg transition-colors">
                            Set Webhook
                        </button>
                        <button onclick="deleteWebhook()"
                                class="px-4 py-2 border border-red-300 hover:bg-red-50 text-red-600 text-sm font-medium rounded-lg transition-colors">
                            Hapus Webhook
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-900">Long Polling (Development Mode)</h3>
                </div>
                <div class="p-5 space-y-3">
                    <p class="text-xs text-gray-500">Untuk development, gunakan perintah artisan berikut di terminal:</p>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 bg-gray-100 text-gray-700 rounded-lg px-3 py-2 text-xs font-mono">php artisan telegram:poll</code>
                        <button onclick="copyText('php artisan telegram:poll')"
                                class="px-3 py-2 text-xs border border-gray-300 hover:bg-gray-50 text-gray-600 font-medium rounded-lg transition-colors">
                            Salin
                        </button>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <a href="{{ route('bot.polling-status') }}" id="btn-polling-status"
                           class="px-4 py-2 border border-[#0E9E8E] hover:bg-[#0E9E8E]/5 text-[#0E9E8E] text-sm font-medium rounded-lg transition-colors">
                            Cek Status Polling
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tab 2: Pendaftaran --}}
<div id="tab-pendaftaran" class="tab-content hidden">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-medium text-gray-900">Daftar Pendaftaran Teknisi</h3>
            <span class="text-xs text-gray-400">Total: {{ $pendaftaran->total() }} pendaftaran</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Nama</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Telegram ID / Username</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Jabatan</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Status</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Diminta</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Diproses Oleh</th>
                        <th class="text-center text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($pendaftaran as $reg)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $reg->name }}</td>
                            <td class="px-5 py-3">
                                <span class="font-mono text-xs text-gray-600">
                                    @if($reg->telegram_username)
                                        {{ $reg->telegram_username }}
                                    @else
                                        {{ $reg->telegram_id ?? '-' }}
                                    @endif
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-600">
                                {{ $reg->requested_jabatan ? ucfirst($reg->requested_jabatan) : '-' }}
                            </td>
                            <td class="px-5 py-3">
                                @if($reg->status === 'pending')
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-700 rounded-full">Pending</span>
                                @elseif($reg->status === 'approved')
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded-full">Disetujui</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700 rounded-full">Ditolak</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-400">{{ $reg->created_at->diffForHumans() }}</td>
                            <td class="px-5 py-3 text-xs text-gray-600">{{ $reg->processedBy?->name ?? '-' }}</td>
                            <td class="px-5 py-3 text-center">
                                @if($reg->status === 'pending')
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick="approveRegistration({{ $reg->id }}, '{{ addslashes($reg->name) }}')"
                                                class="text-green-600 hover:text-green-700 text-xs font-medium">
                                            Setujui
                                        </button>
                                        <button onclick="rejectRegistration({{ $reg->id }}, '{{ addslashes($reg->name) }}')"
                                                class="text-red-500 hover:text-red-600 text-xs font-medium">
                                            Tolak
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400">{{ $reg->processed_at?->diffForHumans() ?? '-' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-400">
                                Belum ada pendaftaran teknisi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pendaftaran->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $pendaftaran->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Tab 3: Teknisi Aktif Bot --}}
<div id="tab-teknisi" class="tab-content hidden">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-medium text-gray-900">Teknisi Terhubung Bot Telegram</h3>
            <span class="text-xs text-gray-400">{{ $teknisiList->count() }} teknisi</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Nama</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Telegram ID</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Username</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Shift</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Terakhir Update</th>
                        <th class="text-center text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($teknisiList as $tek)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ $tek->name }}</td>
                            <td class="px-5 py-3">
                                <span class="font-mono text-xs text-gray-600">{{ $tek->telegram_id ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-600">{{ $tek->telegram_username ? '@'.$tek->telegram_username : '-' }}</td>
                            <td class="px-5 py-3 text-xs text-gray-600">{{ $tek->shift ?? 'reguler' }}</td>
                            <td class="px-5 py-3 text-xs text-gray-400">{{ $tek->updated_at->diffForHumans() }}</td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 rounded-full">Aktif</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-400">
                                Belum ada teknisi yang terhubung ke bot Telegram.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Tab 4: Unknown Assets --}}
<div id="tab-unknown" class="tab-content hidden">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-medium text-gray-900">Asset Tidak Dikenal dari Laporan Bot</h3>
            <span class="text-xs text-gray-400">{{ $unknownAssets->total() }} unknown assets</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Keyword</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Laporan Terkait</th>
                        <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wide px-5 py-3">Dilaporkan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($unknownAssets as $ua)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3">
                                <span class="font-mono text-xs bg-rose-50 text-rose-700 rounded px-2 py-0.5">{{ $ua->keyword_mentioned }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if($ua->maintenanceReport)
                                    <span class="text-xs text-gray-600">#{{ $ua->maintenanceReport->report_code ?? $ua->maintenanceReport->id }}</span>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-400">{{ $ua->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-5 py-8 text-center text-sm text-gray-400">
                                Belum ada unknown assets.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($unknownAssets->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">
                {{ $unknownAssets->links() }}
            </div>
        @endif
    </div>
</div>
</div>

@endsection

@push('scripts')
<script>
// Tab switching
var activeTab = 'status';

function switchTab(tabId) {
    activeTab = tabId;

    // Update button styles
    document.querySelectorAll('.tab-btn').forEach(function(btn) {
        var tab = btn.getAttribute('data-tab');
        if (tab === tabId) {
            btn.classList.remove('border-transparent', 'text-gray-500');
            btn.classList.add('border-[#0E9E8E]', 'text-[#0E9E8E]');
        } else {
            btn.classList.remove('border-[#0E9E8E]', 'text-[#0E9E8E]');
            btn.classList.add('border-transparent', 'text-gray-500');
        }
    });

    // Show/hide content
    document.querySelectorAll('.tab-content').forEach(function(el) {
        el.classList.add('hidden');
    });
    document.getElementById('tab-' + tabId).classList.remove('hidden');
}

// Test koneksi
function testConnection() {
    var btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Testing...';

    fetch('{{ route('bot.test-connection') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            alert(data.message);
        } else {
            alert(data.message || 'Koneksi gagal.');
        }
    })
    .catch(function(err) {
        alert('Gagal menghubungi server: ' + err.message);
    })
    .finally(function() {
        btn.disabled = false;
        btn.textContent = 'Test Koneksi';
    });
}

// Set webhook
function setWebhook() {
    if (!confirm('Set webhook URL ke Telegram? Bot akan mulai menerima update via webhook.')) return;

    var btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Memproses...';

    fetch('{{ route('bot.set-webhook') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            alert(data.message || 'Webhook berhasil disetel.');
        } else {
            alert(data.message || 'Gagal menyetel webhook.');
        }
    })
    .catch(function(err) {
        alert('Gagal: ' + err.message);
    })
    .finally(function() {
        btn.disabled = false;
        btn.textContent = 'Set Webhook';
    });
}

// Delete webhook
function deleteWebhook() {
    if (!confirm('Hapus webhook? Bot tidak akan menerima update via webhook lagi.')) return;

    fetch('{{ route('bot.delete-webhook') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            alert(data.message || 'Webhook berhasil dihapus.');
        } else {
            alert(data.message || 'Gagal menghapus webhook.');
        }
    })
    .catch(function(err) {
        alert('Gagal: ' + err.message);
    });
}

// Approve registrasi
function approveRegistration(id, name) {
    if (!confirm('Setujui pendaftaran ' + name + '?')) return;

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ url('bot/registrations') }}/' + id + '/approve';

    var csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrf);

    document.body.appendChild(form);
    form.submit();
}

// Reject registrasi
function rejectRegistration(id, name) {
    if (!confirm('Tolak pendaftaran ' + name + '?')) return;

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ url('bot/registrations') }}/' + id + '/reject';

    var csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = '_token';
    csrf.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    form.appendChild(csrf);

    document.body.appendChild(form);
    form.submit();
}

// Copy text helper
function copyText(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Teks berhasil disalin.');
    }).catch(function() {
        alert('Gagal menyalin teks.');
    });
}

// Copy webhook URL
function copyWebhookUrl() {
    var input = document.querySelector('#tab-status input[readonly]');
    if (input) {
        navigator.clipboard.writeText(input.value).then(function() {
            alert('URL webhook berhasil disalin.');
        }).catch(function() {
            alert('Gagal menyalin URL.');
        });
    }
}
</script>
@endpush
