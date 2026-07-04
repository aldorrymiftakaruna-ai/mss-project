@extends('layouts.app')

@section('title', 'Pengaturan Rate Biaya')
@section('page-title', 'Pengaturan Rate Biaya')
@section('page-sub', 'Atur tarif downtime per menit dan overtime per jam')

@section('content')
<div class="space-y-6">
    {{-- Form Tambah Rate --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Tambah Rate Baru</h3>
        <form method="POST" action="{{ route('cost.rates.update') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Perusahaan</label>
                <select name="company_id" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-[#0E9E8E] focus:border-[#0E9E8E]">
                    <option value="">Pilih Perusahaan</option>
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Rate Downtime (/menit)</label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-gray-400 text-sm">Rp</span>
                    <input type="number" name="downtime_rate_per_min" step="0.01" min="0" required
                        class="w-full border border-gray-300 rounded-lg pl-10 pr-3 py-2 text-sm focus:ring-[#0E9E8E] focus:border-[#0E9E8E]"
                        placeholder="0">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Rate Overtime (/jam)</label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-gray-400 text-sm">Rp</span>
                    <input type="number" name="overtime_rate_per_hour" step="0.01" min="0" required
                        class="w-full border border-gray-300 rounded-lg pl-10 pr-3 py-2 text-sm focus:ring-[#0E9E8E] focus:border-[#0E9E8E]"
                        placeholder="0">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">Tanggal Berlaku</label>
                <input type="date" name="effective_date" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-[#0E9E8E] focus:border-[#0E9E8E]"
                    value="{{ date('Y-m-d') }}">
            </div>

            <div class="md:col-span-4">
                <button type="submit"
                    class="bg-[#0E9E8E] text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-[#0B8A7C] transition">
                    Simpan Rate
                </button>
            </div>
        </form>
    </div>

    {{-- Tabel Rate Existing --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">Daftar Rate</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left">
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Perusahaan</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Downtime (/menit)</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Overtime (/jam)</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Tanggal Berlaku</th>
                        <th class="pb-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rates as $rate)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-3 font-medium text-gray-900">{{ $rate->company?->name ?? '—' }}</td>
                            <td class="py-3 text-gray-600">Rp {{ number_format($rate->downtime_rate_per_min, 0, ',', '.') }}</td>
                            <td class="py-3 text-gray-600">Rp {{ number_format($rate->overtime_rate_per_hour, 0, ',', '.') }}</td>
                            <td class="py-3 text-gray-600">{{ $rate->effective_date->format('d M Y') }}</td>
                            <td class="py-3 text-gray-500">{{ $rate->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-400">
                                Belum ada rate biaya. Silakan tambah rate baru di atas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
