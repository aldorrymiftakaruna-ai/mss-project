<?php
require 'vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows = DB::table('companies')->get(['id', 'name']);
foreach ($rows as $r) {
    echo "id={$r->id} name={$r->name}\n";
}
