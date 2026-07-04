<?php

namespace Database\Seeders;

use App\Models\AhpCriterion;
use App\Models\AhpPairwise;
use App\Models\AhpSession;
use App\Models\Asset;
use App\Models\Company;
use App\Models\CostRate;
use App\Models\Employee;
use App\Models\MaintenanceReport;
use App\Models\RiskScore;
use App\Models\SparePart;
use App\Models\WeibullResult;
use App\Services\Prescriptive\TopsisService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed data demo untuk semua modul.
     */
    public function run(): void
    {
        // ===== 1. PASTIKAN DATA MASTER =====
        $company = Company::firstOrCreate(
            ['name' => 'PT Demo Maintenance'],
            ['code' => 'DM01']
        );

        // ===== 2. EMPLOYEE =====
        $teknisi = Employee::firstOrCreate(
            ['telegram_id' => null, 'name' => 'Budi Teknisi'],
            [
                'company_id'   => $company->id,
                'role'         => 'teknisi',
                'is_active'    => true,
            ]
        );

        $supervisor = Employee::firstOrCreate(
            ['telegram_id' => null, 'name' => 'Ahmad Supervisor'],
            [
                'company_id'   => $company->id,
                'role'         => 'foreman',
                'is_active'    => true,
            ]
        );

        // ===== 3. ASSETS (3-5) =====
        $assetData = [
            ['tag_no' => 'PU-101', 'description' => 'Pump Centrifugal 101', 'motor_kw' => 37],
            ['tag_no' => 'FN-201', 'description' => 'Fan Exhaust 201', 'motor_kw' => 15],
            ['tag_no' => 'CM-301', 'description' => 'Compressor Screw 301', 'motor_kw' => 75],
            ['tag_no' => 'CV-401', 'description' => 'Conveyor Belt 401', 'motor_kw' => 11],
        ];

        $assets = [];
        foreach ($assetData as $data) {
            $assets[] = Asset::firstOrCreate(
                ['tag_no' => $data['tag_no']],
                [
                    'company_id'   => $company->id,
                    'description'  => $data['description'],
                    'motor_kw'     => $data['motor_kw'],
                    'status'       => 'normal',
                ]
            );
        }

        // ===== 4. SPARE PARTS =====
        $sparepartData = [
            ['kode_material' => 'BRG-6205', 'deskripsi' => 'Bearing SKF 6205', 'stok_tersedia' => 20, 'kategori' => 'Bearing', 'satuan' => 'Pcs', 'stok_minimum' => 2],
            ['kode_material' => 'OIL-46', 'deskripsi' => 'Hydraulic Oil ISO 46', 'stok_tersedia' => 50, 'kategori' => 'Lubricant', 'satuan' => 'Liter', 'stok_minimum' => 10],
            ['kode_material' => 'SEAL-25', 'deskripsi' => 'Mechanical Seal 25mm', 'stok_tersedia' => 10, 'kategori' => 'Seal', 'satuan' => 'Pcs', 'stok_minimum' => 2],
        ];

        foreach ($sparepartData as $data) {
            SparePart::firstOrCreate(
                ['kode_material' => $data['kode_material']],
                $data
            );
        }

        // ===== 5. MAINTENANCE REPORTS (10-15) =====
        if (MaintenanceReport::count() < 3) {
            $months = [1, 2, 3, 4, 5, 6];
            foreach ($months as $i => $monthOffset) {
                $asset = $assets[array_rand($assets)];
                MaintenanceReport::create([
                    'asset_id'           => $asset->id,
                    'reported_by'        => $teknisi->id,
                    'shift'              => 'reguler',
                    'tanggal'            => now()->subMonths(6 - $i)->format('Y-m-d'),
                    'jenis'              => 'corrective',
                    'deskripsi_masalah'  => "Demo: masalah pada {$asset->tag_no}",
                    'tindakan'           => 'Penggantian bearing dan pelumasan',
                    'report_code'        => 'RPT-DEMO-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'work_duration_minutes' => rand(60, 480),
                    'downtime_minutes'    => rand(30, 360),
                    'root_cause'         => 'Keausan normal',
                    'is_overtime'        => rand(0, 1),
                    'overtime_hours'     => rand(0, 4),
                    'status'             => 'selesai',
                    'submitted_at'       => now()->subMonths(6 - $i),
                ]);
            }
        }

        // ===== 6. COST RATE =====
        CostRate::firstOrCreate(
            ['company_id' => $company->id, 'effective_date' => now()->startOfYear()],
            [
                'downtime_rate_per_min'   => 5000,
                'overtime_rate_per_hour'  => 75000,
            ]
        );

        // ===== 7. AHP SESSION (demo) =====
        $ahpSession = AhpSession::firstOrCreate(
            ['name' => 'Demo - Prioritas Asset'],
            ['is_final' => true, 'consistency_ratio' => 0.05]
        );

        if ($ahpSession->criteria()->count() === 0) {
            $kriteriaNames = [
                'cm_findings'  => 'Jumlah Temuan CM',
                'avg_severity' => 'Rata-rata Severity',
                'downtime'     => 'Total Downtime',
                'report_count' => 'Frekuensi Laporan',
                'mtbf_days'    => 'MTBF (hari)',
            ];

            foreach ($kriteriaNames as $name => $label) {
                AhpCriterion::create([
                    'ahp_session_id' => $ahpSession->id,
                    'name'           => $name,
                    'label'          => $label,
                    'weight'         => 0.2,
                ]);
            }
        }

        // ===== 8. RISK SCORES (demo) =====
        foreach ($assets as $asset) {
            $score = round(mt_rand(10, 90) / 100, 2);
            $category = $score < 0.3 ? 'rendah' : ($score < 0.6 ? 'sedang' : 'tinggi');

            RiskScore::updateOrCreate(
                ['asset_id' => $asset->id],
                [
                    'score'          => $score,
                    'category'       => $category,
                    'parameters_json' => [
                        'vibration_slope_norm' => round($score * 0.4, 3),
                        'temperature_slope_norm' => round($score * 0.3, 3),
                        'vibration_level_norm' => round($score * 0.2, 3),
                        'rate_of_change_norm' => round($score * 0.1, 3),
                    ],
                    'calculated_at'  => now(),
                ]
            );
        }

        // ===== 9. WEIBULL (demo) =====
        foreach ($assets as $asset) {
            WeibullResult::updateOrCreate(
                ['asset_id' => $asset->id],
                [
                    'beta'                  => round(mt_rand(80, 200) / 100, 2),
                    'eta'                   => round(mt_rand(50, 200), 0),
                    'mttf'                  => round(mt_rand(60, 250), 0),
                    'reliability_at_period' => round(mt_rand(5000, 9500) / 10000, 4),
                    'calculated_at'         => now(),
                ]
            );
        }

        $this->command->info('Demo data berhasil di-seed:');
        $this->command->info("- 1 Company, 2 Employees, " . count($assets) . " Assets");
        $this->command->info("- " . count($sparepartData) . " Spare Parts");
        $this->command->info("- AHP Session + Criteria");
        $this->command->info("- Risk Scores + Weibull Results");
    }
}
