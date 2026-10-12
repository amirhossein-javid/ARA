<?php

declare(strict_types=1);

use Ara\Http\Controllers\AdminAuthController;
use Ara\Http\Controllers\AdminDashboardController;
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
    $authController = new AdminAuthController(
        $container['app'],
        $container['database'],
        $container['auth'],
        $privatePath
    );
    $dashboardController = new AdminDashboardController(
        $container['app'],
        $container['database'],
        $container['auth'],
        $privatePath
    );
    $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    if ($requestPath === '/admin/login' && $requestMethod === 'GET') {
        $authController->showLogin();
    }

    if ($requestPath === '/admin/login' && $requestMethod === 'POST') {
        $authController->login();
    }

    if ($requestPath === '/admin/logout' && $requestMethod === 'POST') {
        $authController->logout();
    }

    if ($requestPath === '/admin' && $requestMethod === 'GET') {
        $dashboardController->dashboard();
    }

    if ($requestPath === '/admin/consultations' && $requestMethod === 'GET') {
        $dashboardController->consultations();
    }

    $consultationRoute = [];

    if (preg_match('#^/admin/consultations/([1-9][0-9]*)(?:/(status|notes|delete))?$#D', $requestPath, $consultationRoute) === 1) {
        $requestId = filter_var($consultationRoute[1], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => PHP_INT_MAX],
        ]);
        $action = $consultationRoute[2] ?? '';

        if ($requestId !== false && $action === '' && $requestMethod === 'GET') {
            $dashboardController->consultation($requestId);
        }

        if ($requestId !== false && $action === 'status' && $requestMethod === 'POST') {
            $dashboardController->updateStatus($requestId);
        }

        if ($requestId !== false && $action === 'notes' && $requestMethod === 'POST') {
            $dashboardController->updateNotes($requestId);
        }

        if ($requestId !== false && $action === 'delete' && $requestMethod === 'POST') {
            $dashboardController->deleteConsultation($requestId);
        }

        if ($requestId !== false) {
            $dashboardController->methodNotAllowed($action === '' ? ['GET'] : ['POST']);
        }
    }

    $allowedMethods = match ($requestPath) {
        '/admin/login' => ['GET', 'POST'],
        '/admin/logout' => ['POST'],
        '/admin' => ['GET'],
        '/admin/consultations' => ['GET'],
        default => [],
    };

    if ($allowedMethods !== []) {
        if (in_array($requestPath, ['/admin/login', '/admin/logout'], true)) {
            $authController->methodNotAllowed($allowedMethods);
        }

        $dashboardController->methodNotAllowed($allowedMethods);
    }

    $dashboardController->notFound();
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
