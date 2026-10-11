<?php

declare(strict_types=1);

use Ara\Config\Environment;

return [
    'host' => Environment::get('DB_HOST', 'localhost'),
    'port' => (int) Environment::get('DB_PORT', '3306'),
    'database' => Environment::require('DB_DATABASE'),
    'username' => Environment::require('DB_USERNAME'),
    'password' => Environment::require('DB_PASSWORD'),
    'charset' => Environment::get('DB_CHARSET', 'utf8mb4'),
];
