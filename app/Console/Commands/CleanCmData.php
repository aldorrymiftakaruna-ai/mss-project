<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CmMeasurement;
use App\Models\CmFinding;
use App\Models\Asset;

class CleanCmData extends Command
{
    protected $signature = 'cm:clean
        {--all : Hapus semua data CM}
        {--measurements : Hapus semua pengukuran}
        {--findings : Hapus semua temuan visual}
        {--asset= : Hapus data CM untuk equipment tertentu (Tag No)}
        {--before= : Hapus data sebelum tanggal (YYYY-MM-DD)}
        {--after= : Hapus data setelah tanggal (YYYY-MM-DD)}';

    protected $description = 'Bersihkan data Condition Monitoring (CM)';

    public function handle()
    {
        $queryMeasurements = CmMeasurement::query();
        $queryFindings = CmFinding::query();
        $label = '';

        if ($this->option('all')) {
            $label = 'SEMUA DATA CM';
        } elseif ($this->option('measurements')) {
            $queryFindings = null;
            $label = 'SEMUA PENGUKURAN';
        } elseif ($this->option('findings')) {
            $queryMeasurements = null;
            $label = 'SEMUA TEMUAN VISUAL';
        } elseif ($this->option('asset')) {
            $tag = $this->option('asset');
            $asset = Asset::where('tag_no', $tag)->first();
            if (!$asset) {
                $this->error("Equipment dengan Tag No '{$tag}' tidak ditemukan.");
                return 1;
            }
            $queryMeasurements->where('asset_id', $asset->id);
            $queryFindings->where('asset_id', $asset->id);
            $label = "DATA CM UNTUK EQUIPMENT '{$tag}'";
        }

        if ($this->option('before')) {
            $date = $this->option('before');
            $queryMeasurements?->whereDate('tanggal', '<', $date);
            $queryFindings?->whereDate('tanggal', '<', $date);
            $label = $label ? "$label SEBELUM $date" : "DATA CM SEBELUM $date";
        }

        if ($this->option('after')) {
            $date = $this->option('after');
            $queryMeasurements?->whereDate('tanggal', '>', $date);
            $queryFindings?->whereDate('tanggal', '>', $date);
            $label = $label ? "$label SETELAH $date" : "DATA CM SETELAH $date";
        }

        if (!$label) {
            $this->info('Gunakan opsi --all, --measurements, --findings, --asset=, --before=, atau --after=');
            $this->info('Contoh: php artisan cm:clean --all');
            $this->info('        php artisan cm:clean --asset=P-6163P7');
            $this->info('        php artisan cm:clean --before=2024-06-01');
            return 0;
        }

        $countMeasurements = $queryMeasurements?->count() ?? 0;
        $countFindings = $queryFindings?->count() ?? 0;
        $total = $countMeasurements + $countFindings;

        if ($total === 0) {
            $this->warn("Tidak ada data yang cocok untuk {$label}.");
            return 0;
        }

        if (!$this->confirm("Yakin hapus {$label}? ({$total} data)")) {
            $this->info('Dibatalkan.');
            return 0;
        }

        if ($queryMeasurements) {
            $queryMeasurements->delete();
            $this->info("{$countMeasurements} pengukuran dihapus.");
        }

        if ($queryFindings) {
            $queryFindings->delete();
            $this->info("{$countFindings} temuan visual dihapus.");
        }

        // Reset status asset
        $updated = Asset::whereIn('status', ['alarm', 'danger'])->update(['status' => 'normal']);
        $this->info("{$updated} asset status di-reset ke normal.");

        $this->info("Selesai! {$total} data CM berhasil dihapus.");
    }
}
