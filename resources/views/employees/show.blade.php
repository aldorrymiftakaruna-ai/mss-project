@extends('layouts.app')

@section('title', 'Detail Karyawan')
@section('page-title', $employee->name)
@section('page-sub', '— ' . ucfirst($employee->role))

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
    {{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left: Info Karyawan --}}
    <div class="lg:col-span-1 space-y-6">

        {{-- Kartu Identitas --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <div class="flex items-center gap-4 mb-5">
                <div class="w-14 h-14 rounded-full bg-[#0E9E8E]/10 flex items-center justify-center text-xl font-bold text-[#0E9E8E]">
                    {{ strtoupper(substr($employee->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="font-semibold text-gray-800">{{ $employee->name }}</h2>
                    <span class="text-xs text-gray-400 capitalize">{{ $employee->role }}</span>
                </div>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-400">Status</span>
                    @if($employee->is_active)
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Nonaktif</span>
                    @endif
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Shift</span>
                    <span class="text-gray-600 capitalize">{{ $employee->shift ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Perusahaan</span>
                    <span class="text-gray-600">{{ $employee->company->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Telegram</span>
                    <span class="text-gray-600">
                        @if($employee->telegram_username)
                            @{{ $employee->telegram_username }}
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Telegram ID</span>
                    <span class="text-gray-600">{{ $employee->telegram_id ?? '—' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Bergabung</span>
                    <span class="text-gray-600">{{ $employee->created_at->format('d M Y') }}</span>
                </div>
            </div>

            <hr class="my-4 border-gray-100">

            {{-- Tombol Aksi --}}
            <div class="flex gap-2">
                <button onclick="document.getElementById('modal-edit').classList.remove('hidden')"
                    class="flex-1 text-center bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">
                    Edit
                </button>
                <form action="{{ route('employees.destroy', $employee) }}" method="POST"
                    onsubmit="return confirm('Hapus karyawan ini?')" class="flex-1">
                    @csrf @method('DELETE')
                    <button class="w-full border border-red-200 text-red-500 py-2 rounded-lg text-sm hover:bg-red-50 transition">
                        Hapus
                    </button>
                </form>
            </div>
        </div>

    </div>

    {{-- Right: Riwayat Pekerjaan --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Statistik Ringkas --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-2xl font-bold text-[#0E9E8E]">{{ $employee->manpowerLogs->count() }}</div>
                <div class="text-xs text-gray-400 mt-1">Total Pekerjaan</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-2xl font-bold text-blue-500">
                    {{ $employee->manpowerLogs->where('keterangan', 'selesai')->count() }}
                </div>
                <div class="text-xs text-gray-400 mt-1">Selesai</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-2xl font-bold text-yellow-500">
                    {{ $employee->manpowerLogs->where('keterangan', 'proses')->count() }}
                </div>
                <div class="text-xs text-gray-400 mt-1">Dalam Proses</div>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
                <div class="text-2xl font-bold text-gray-500">
                    {{ $employee->manpowerLogs->sum('durasi_menit') }} mnt
                </div>
                <div class="text-xs text-gray-400 mt-1">Total Durasi</div>
            </div>
        </div>

        {{-- Daftar Riwayat Pekerjaan --}}
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-700 text-sm">Riwayat Pekerjaan</h3>
            </div>

            @if($employee->manpowerLogs->count() > 0)
                <div class="divide-y divide-gray-50">
                    @foreach($employee->manpowerLogs as $log)
                    <div class="px-5 py-4 hover:bg-gray-50/50">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    @if($log->maintenanceReport)
                                        <a href="{{ route('maintenance.show', $log->maintenanceReport) }}"
                                            class="text-sm font-medium text-[#0E9E8E] hover:underline truncate">
                                            {{ $log->maintenanceReport->asset->name ?? '—' }}
                                        </a>
                                    @else
                                        <span class="text-sm text-gray-500">Laporan tidak tersedia</span>
                                    @endif
                                    @if($log->keterangan == 'selesai')
                                        <span class="px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 shrink-0">Selesai</span>
                                    @elseif($log->keterangan == 'proses')
                                        <span class="px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-700 shrink-0">Proses</span>
                                    @elseif($log->keterangan)
                                        <span class="px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 shrink-0">{{ $log->keterangan }}</span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-400">
                                    @if($log->maintenanceReport)
                                        <span>{{ $log->maintenanceReport->tanggal ? $log->maintenanceReport->tanggal->format('d M Y') : '' }}</span>
                                        <span class="capitalize">{{ $log->maintenanceReport->jenis }}</span>
                                        <span class="capitalize">{{ $log->maintenanceReport->shift }}</span>
                                    @endif
                                    @if($log->jam_mulai && $log->jam_selesai)
                                        <span>{{ $log->jam_mulai }} - {{ $log->jam_selesai }}</span>
                                    @endif
                                    @if($log->durasi_menit)
                                        <span>{{ $log->durasi_menit }} menit</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="px-5 py-10 text-center text-gray-400 text-sm">
                    Belum ada riwayat pekerjaan.
                </div>
            @endif
        </div>

    </div>
</div>

{{-- Modal Edit --}}
<div id="modal-edit" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Edit Karyawan</h3>
            <button onclick="document.getElementById('modal-edit').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>

        <form action="{{ route('employees.update', $employee) }}" method="POST" class="space-y-4">
            @csrf @method('PUT')

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Nama *</label>
                <input type="text" name="name" value="{{ old('name', $employee->name) }}" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Role *</label>
                    <select name="role" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="teknisi" {{ $employee->role == 'teknisi' ? 'selected' : '' }}>Teknisi</option>
                        <option value="foreman" {{ $employee->role == 'foreman' ? 'selected' : '' }}>Foreman</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Shift</label>
                    <select name="shift" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">—</option>
                        <option value="shift" {{ $employee->shift == 'shift' ? 'selected' : '' }}>Shift</option>
                        <option value="reguler" {{ $employee->shift == 'reguler' ? 'selected' : '' }}>Reguler</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Telegram Username</label>
                <input type="text" name="telegram_username" value="{{ old('telegram_username', $employee->telegram_username) }}"
                    placeholder="@username"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>

            <div>
                <label class="text-xs text-gray-500 mb-1 block">Telegram ID</label>
                <input type="text" name="telegram_id" value="{{ old('telegram_id', $employee->telegram_id) }}"
                    placeholder="123456789"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" id="is_active"
                    {{ $employee->is_active ? 'checked' : '' }}
                    class="rounded border-gray-300 text-[#0E9E8E] focus:ring-[#0E9E8E]">
                <label for="is_active" class="text-sm text-gray-600">Aktif</label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">
                    Simpan Perubahan
                </button>
                <button type="button" onclick="document.getElementById('modal-edit').classList.add('hidden')"
                    class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
