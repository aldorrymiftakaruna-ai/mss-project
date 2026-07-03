<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MaintenanceReport extends Model
{
    protected $fillable = [
        'asset_id',
        'reported_by',
        'shift',
        'tanggal',
        'jenis',
        'deskripsi_masalah',
        'tindakan',
        'report_code',
        'work_duration_minutes',
        'root_cause',
        'photo_documentation',
        'wizard_started_at',
        'submitted_at',
        'ai_suggestion_json',
        'status',
        'foto_path',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'photo_documentation' => 'array',
        'ai_suggestion_json' => 'array',
        'wizard_started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'work_duration_minutes' => 'integer',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function reporter()
    {
        return $this->belongsTo(Employee::class, 'reported_by');
    }

    public function manpowerLogs()
    {
        return $this->hasMany(ManpowerLog::class);
    }

    /**
     * Accessor: array URL publik foto dokumentasi.
     * Disk diambil dari config('telegram.photo_disk') agar selalu sinkron
     * dengan disk yang dipakai PhotoStorageService saat menyimpan foto.
     *
     * @return array
     */
    public function getPhotoDocumentationUrlsAttribute(): array
    {
        $disk  = config('telegram.photo_disk', 'public');
        $paths = $this->photo_documentation ?? [];

        if (empty($paths)) {
            return [];
        }

        $urls = [];
        foreach ($paths as $path) {
            try {
                $urls[] = Storage::disk($disk)->url($path);
            } catch (\Throwable $e) {
                // Fallback: langsung ke /storage/ path jika disk error
                $urls[] = url('storage/' . ltrim($path, '/'));
            }
        }

        return $urls;
    }
}
