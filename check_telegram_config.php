<?php
$result = include __DIR__ . '/config/telegram.php';
echo 'type: ' . gettype($result) . "\n";
echo 'value: ' . var_export($result, true) . "\n";
echo 'file size: ' . filesize(__DIR__ . '/config/telegram.php') . "\n";

// Cek byte pertama file
$content = file_get_contents(__DIR__ . '/config/telegram.php');
echo 'total chars: ' . strlen($content) . "\n";
echo 'ord byte 0: ' . ord($content[0]) . "\n";
echo 'ord byte 1: ' . ord($content[1]) . "\n";
echo 'ord byte 2: ' . ord($content[2]) . "\n";
echo '5 chars pertama: ' . bin2hex(substr($content, 0, 5)) . "\n";

