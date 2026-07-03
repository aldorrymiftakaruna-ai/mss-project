<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = resolve(App\Services\Telegram\ReportWizardService::class);
$ref = new ReflectionMethod($service, 'extractKeywords');
$ref->setAccessible(true);

echo "Test 1: 'rs 01'\n";
$r1 = $ref->invoke($service, 'rs 01');
echo json_encode($r1) . "\n\n";

echo "Test 2: 'ganti bearing rs01'\n";
$r2 = $ref->invoke($service, 'ganti bearing rs01');
echo json_encode($r2) . "\n\n";

echo "Test 3: 'ganti daun screw sc06 4 jam'\n";
$r3 = $ref->invoke($service, 'ganti daun screw sc06 4 jam');
echo json_encode($r3) . "\n\n";

echo "Test 4: 'p01 bocor 30 menit'\n";
$r4 = $ref->invoke($service, 'p01 bocor 30 menit');
echo json_encode($r4) . "\n";
