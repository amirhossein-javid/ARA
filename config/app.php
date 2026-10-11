<?php

declare(strict_types=1);

use Ara\Config\Environment;

return [
    'environment' => Environment::get('APP_ENV', 'production'),
    'debug' => Environment::bool('APP_DEBUG', false),
    'url' => Environment::get('APP_URL', 'https://ara-studio.ir'),
    'timezone' => Environment::get('APP_TIMEZONE', 'Asia/Tehran'),
];
