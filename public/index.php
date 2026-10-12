<?php

declare(strict_types=1);

use Ara\Http\Controllers\AdminAuthController;
use Ara\Http\Controllers\ConsultationController;
use Ara\Security\Csrf;

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
header_remove('X-Powered-By');

$configuredPrivatePath = $_SERVER['ARA_PRIVATE_PATH'] ?? getenv('ARA_PRIVATE_PATH');
$privatePathCandidates = [];

if (is_string($configuredPrivatePath) && trim($configuredPrivatePath) !== '') {
    $privatePathCandidates[] = rtrim(trim($configuredPrivatePath), '/\\');
}

$privatePathCandidates[] = dirname(__DIR__) . '/ara-studio';
$privatePathCandidates[] = dirname(__DIR__);
$privatePath = null;

foreach (array_unique($privatePathCandidates) as $candidate) {
    if (is_file($candidate . '/bootstrap/app.php')) {
        $privatePath = $candidate;
        break;
    }
}

if ($privatePath === null) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Application configuration is unavailable.';
    exit;
}

/** @var array{app: array<string, mixed>, database: array<string, mixed>, consultation: array<string, mixed>, auth: array<string, mixed>} $container */
$container = require $privatePath . '/bootstrap/app.php';

$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rtrim($requestPath, '/') : '/';
$requestPath = $requestPath === '' ? '/' : $requestPath;

if ($requestPath === '/api/consultation') {
    (new ConsultationController(
        $container['app'],
        $container['database'],
        $container['consultation'],
        $privatePath . '/storage'
    ))->handle();
}

if ($requestPath === '/admin' || str_starts_with($requestPath, '/admin/')) {
    $adminController = new AdminAuthController(
        $container['app'],
        $container['database'],
        $container['auth'],
        $privatePath
    );
    $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($requestPath === '/admin/login' && $requestMethod === 'GET') {
        $adminController->showLogin();
    }

    if ($requestPath === '/admin/login' && $requestMethod === 'POST') {
        $adminController->login();
    }

    if ($requestPath === '/admin/logout' && $requestMethod === 'POST') {
        $adminController->logout();
    }

    if ($requestPath === '/admin' && $requestMethod === 'GET') {
        $adminController->dashboard();
    }

    $allowedMethods = match ($requestPath) {
        '/admin/login' => ['GET', 'POST'],
        '/admin/logout' => ['POST'],
        '/admin' => ['GET'],
        default => [],
    };

    if ($allowedMethods !== []) {
        $adminController->methodNotAllowed($allowedMethods);
    }
}

if (!in_array($requestPath, ['/', '/index.php'], true)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Not found.';
    exit;
}

if (!in_array(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), ['GET', 'HEAD'], true)) {
    http_response_code(405);
    header('Allow: GET, HEAD');
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Method not allowed.';
    exit;
}

$csrfToken = Csrf::token();
header('Cache-Control: no-store');

require $privatePath . '/resources/views/home.php';
