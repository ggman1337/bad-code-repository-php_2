<?php

return [
    'driver' => 'sqlite',
    'database' => getenv('APP_DATABASE') ?: BASE_PATH . '/storage/database.sqlite',
];
