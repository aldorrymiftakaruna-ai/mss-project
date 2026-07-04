<?php
try {
    $cols = DB::select('SHOW COLUMNS FROM employees');
    foreach ($cols as $c) {
        echo $c->Field . ' (' . $c->Type . ')' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
