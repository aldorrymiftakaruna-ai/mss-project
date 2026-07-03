@extends('layouts.app')

@section('title', 'Karyawan')
@section('page-title', 'Karyawan')
@section('page-sub', '— Data teknisi & foreman')

@section('content')

@if(session('success'))
<div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
    {{ session('success') }}
</div>
@endif

<div class="flex items-center justify-between mb-5">
    <select class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white">
                <option value="">Semua Role</option>
        <option value="supervisor">Supervisor</option>
        <option value="foreman">Foreman</option>
        <option value="teknisi">Teknisi</option>
    </select>
    <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
        class="bg-[#0E9E8E] text-white text-sm px-4 py-2 rounded-lg hover:bg-[#0a7a6d] transition">
        + Tambah Karyawan
    </button>
</div>

<div class="bg-white rounded-xl border border-gray-200">
    <table class="w-full text-sm">
        <thead class="border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase">
                <th class="px-5 py-3 text-left">Nama</th>
                <th class="px-5 py-3 text-left">Role</th>
                <th class="px-5 py-3 text-left">Shift</th>
                <th class="px-5 py-3 text-left">Telegram</th>
                <th class="px-5 py-3 text-left">Status</th>
                <th class="px-5 py-3 text-left">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($employees as $employee)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3 font-medium">
                    <a href="{{ route('employees.show', $employee) }}" class="text-[#0E9E8E] hover:underline">
                        {{ $employee->name }}
                    </a>
                </td>
                <td class="px-5 py-3 capitalize text-gray-600">{{ $employee->role }}</td>
                                <td class="px-5 py-3 text-gray-500 capitalize">
                    {{ $employee->shift ?? '—' }}
                </td>
                <td class="px-5 py-3 text-gray-500">
                    @if($employee->telegram_username)
                        <span class="text-[#0E9E8E]">{{ $employee->telegram_username }}</span>
                    @else
                        <span class="text-gray-300 text-xs">Belum terhubung</span>
                    @endif
                </td>
                <td class="px-5 py-3">
                    @if($employee->is_active)
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Nonaktif</span>
                    @endif
                </td>
                <td class="px-5 py-3">
                    <form action="{{ route('employees.destroy', $employee) }}" method="POST"
                        onsubmit="return confirm('Hapus karyawan ini?')">
                        @csrf @method('DELETE')
                        <button class="text-red-400 hover:text-red-600 text-xs">Hapus</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-5 py-10 text-center text-gray-400">Belum ada data karyawan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Modal Tambah --}}
<div id="modal-add" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-gray-800">Tambah Karyawan</h3>
            <button onclick="document.getElementById('modal-add').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
                <form action="{{ route('employees.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-xs text-gray-500 mb-1 block">Nama *</label>
                        <input type="text" name="name" required placeholder="Nama lengkap"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Role *</label>
                                        <select name="role" required class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="teknisi">Teknisi</option>
                        <option value="foreman">Foreman</option>
                        <option value="supervisor">Supervisor</option>
                    </select>
                </div>
                                <div>
                    <label class="text-xs text-gray-500 mb-1 block">Shift</label>
                    <select name="shift" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm">
                        <option value="">—</option>
                        <option value="shift">Shift</option>
                        <option value="reguler">Reguler</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-[#0E9E8E] text-white py-2 rounded-lg text-sm hover:bg-[#0a7a6d] transition">
                    Simpan
                </button>
                <button type="button" onclick="document.getElementById('modal-add').classList.add('hidden')"
                    class="flex-1 border border-gray-200 text-gray-600 py-2 rounded-lg text-sm hover:bg-gray-50 transition">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

@endsection