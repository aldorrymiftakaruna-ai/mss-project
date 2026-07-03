<?php

return [
    'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
    'bot_username' => env('TELEGRAM_BOT_USERNAME', ''),
    'photo_disk' => env('TELEGRAM_PHOTO_DISK', 'local'),
    'photo_folder' => env('TELEGRAM_PHOTO_FOLDER', 'reports'),
    'photo_max_bytes' => env('TELEGRAM_PHOTO_MAX_BYTES', 20 * 1024 * 1024),
];
