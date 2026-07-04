<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CmMeasurement;
use Illuminate\Support\Facades\DB;

$fieldsVib = [
    'driver_de_vib_v', 'driver_de_vib_h', 'driver_de_vib_a',
    'driver_nde_vib_v', 'driver_nde_vib_h', 'driver_nde_vib_a',
    'driven_de_vib_v', 'driven_de_vib_h', 'driven_de_vib_a',
    'driven_nde_vib_v', 'driven_nde_vib_h', 'driven_nde_vib_a',
];
$fieldsTemp = [
    'driver_de_temp', 'driver_nde_temp',
    'driven_de_temp', 'driven_nde_temp',
];

// Simulasi method cmMeasurementsAlert
$latestIds = CmMeasurement::select(DB::raw('MAX(id) as id'))
    ->groupBy('asset_id')
    ->pluck('id');

echo "Total asset unik dengan measurement: " . $latestIds->count() . "\n";

$latestPerAsset = CmMeasurement::whereIn('id', $latestIds)
    ->with('asset:id,tag_no,description')
    ->get();

echo "Data yang diambil: " . $latestPerAsset->count() . "\n\n";

$overVibrasi = collect();
$overTemp    = collect();
$totalDanger = 0;

foreach ($latestPerAsset as $m) {
    $vibMax = 0;
    $tempMax = 0;

    foreach ($fieldsVib as $f) {
        $v = (float) ($m->$f ?? 0);
        if ($v > $vibMax) $vibMax = $v;
    }
    foreach ($fieldsTemp as $f) {
        $t = (float) ($m->$f ?? 0);
        if ($t > $tempMax) $tempMax = $t;
    }

    if ($vibMax >= 7.0) {
        $overVibrasi->push([
            'tag' => $m->asset->tag_no ?? '—',
            'vib' => $vibMax,
        ]);
        $totalDanger++;
    }
    if ($tempMax >= 85) {
        $overTemp->push([
            'tag'  => $m->asset->tag_no ?? '—',
            'temp' => $tempMax,
        ]);
        if ($vibMax < 7.0) $totalDanger++;
    }
}

echo "Total danger: $totalDanger\n";
echo "Over vibrasi count (sebelum take): " . $overVibrasi->count() . "\n";
echo "Over temp count (sebelum take): " . $overTemp->count() . "\n\n";

// Urutkan
$sortedVib = $overVibrasi->sortByDesc('vib')->values();
$sortedTemp = $overTemp->sortByDesc('temp')->values();

echo "=== VIBRASI >= 7.0 (semua, " . $sortedVib->count() . ") ===\n";
foreach ($sortedVib as $i => $v) {
    echo ($i+1) . ". {$v['tag']} = {$v['vib']} mm/s\n";
}

echo "\n=== TEMPERATURE >= 85 (semua, " . $sortedTemp->count() . ") ===\n";
foreach ($sortedTemp as $i => $v) {
    echo ($i+1) . ". {$v['tag']} = {$v['temp']} °C\n";
}

echo "\n=== AMBIL take(5) untuk ditampilkan ===\n";
echo "Vibrasi take(5): " . $sortedVib->take(5)->count() . "\n";
echo "Temp take(5): " . $sortedTemp->take(5)->count() . "\n";
