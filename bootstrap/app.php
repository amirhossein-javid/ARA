<?php

declare(strict_types=1);

use Ara\Config\Environment;
use Ara\Database\Connection;
use Ara\Http\ErrorHandler;
use Ara\Http\SecurityHeaders;

define('ARA_BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    $prefix = 'Ara\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $path = ARA_BASE_PATH . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

require ARA_BASE_PATH . '/app/Support/helpers.php';

$logFile = ARA_BASE_PATH . '/storage/logs/app.log';
ErrorHandler::register('production', $logFile, false);
Environment::load(ARA_BASE_PATH . '/.env');

$app = require ARA_BASE_PATH . '/config/app.php';
$database = require ARA_BASE_PATH . '/config/database.php';
$consultation = require ARA_BASE_PATH . '/config/consultation.php';

if (!in_array($app['environment'], ['local', 'development', 'testing', 'production'], true)) {
    throw new \RuntimeException('APP_ENV has an unsupported value.');
}

if (filter_var($app['url'], FILTER_VALIDATE_URL) === false) {
    throw new \RuntimeException('APP_URL must be a valid absolute URL.');
}

if (!in_array((string) parse_url($app['url'], PHP_URL_SCHEME), ['http', 'https'], true)) {
    throw new \RuntimeException('APP_URL must use HTTP or HTTPS.');
}

if (!in_array($app['timezone'], timezone_identifiers_list(), true)) {
    throw new \RuntimeException('APP_TIMEZONE is invalid.');
}

if ($app['environment'] === 'production' && $app['debug']) {
    throw new \RuntimeException('APP_DEBUG must be disabled in production.');
}

if (strlen($app['key']) < 32 || str_starts_with($app['key'], 'replace_')) {
    throw new \RuntimeException('APP_KEY must be replaced with a private value of at least 32 characters.');
}

date_default_timezone_set($app['timezone']);
ErrorHandler::register($app['environment'], $logFile, $app['debug']);
SecurityHeaders::send($app['environment']);
Connection::validateConfig($database);

return [
    'app' => $app,
    'database' => $database,
    'consultation' => $consultation,
];
