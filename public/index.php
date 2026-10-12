<?php

declare(strict_types=1);

use Ara\Http\Controllers\AdminAuthController;
use Ara\Http\Controllers\AdminDashboardController;
use Ara\Http\Controllers\AdminManagementController;
use Ara\Http\Controllers\AdminPortfolioController;
use Ara\Http\Controllers\ConsultationController;
use Ara\Database\Connection;
use Ara\Portfolio\PortfolioRepository;
use Ara\Portfolio\TemporaryPortfolio;
use Ara\Portfolio\PortfolioValidator;
use Ara\Security\Csrf;
use Ara\Settings\SiteSettings;
use Ara\Settings\SiteSettingsRepository;

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

/** @var array{app: array<string, mixed>, database: array<string, mixed>, consultation: array<string, mixed>, auth: array<string, mixed>, portfolio: array<string, mixed>} $container */
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
    $portfolioController = new AdminPortfolioController(
        $container['app'],
        $container['database'],
        $container['auth'],
        $container['portfolio'],
        $privatePath,
        __DIR__
    );
    $managementController = new AdminManagementController(
        $container['app'], $container['database'], $container['auth'], $privatePath
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

    if ($requestPath === '/admin/portfolio' && $requestMethod === 'GET') {
        $portfolioController->index();
    }

    if ($requestPath === '/admin/portfolio/create' && $requestMethod === 'GET') {
        $portfolioController->createForm();
    }

    if ($requestPath === '/admin/portfolio/create' && $requestMethod === 'POST') {
        $portfolioController->create();
    }

    if ($requestPath === '/admin/admins' && $requestMethod === 'GET') {
        $managementController->admins();
    }
    if ($requestPath === '/admin/admins/create' && $requestMethod === 'GET') {
        $managementController->createForm();
    }
    if ($requestPath === '/admin/admins/create' && $requestMethod === 'POST') {
        $managementController->create();
    }
    if ($requestPath === '/admin/settings' && $requestMethod === 'GET') {
        $managementController->settings();
    }
    if ($requestPath === '/admin/settings' && $requestMethod === 'POST') {
        $managementController->saveSettings();
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

    $portfolioRoute = [];

    if (preg_match('#^/admin/portfolio/([1-9][0-9]*)(?:/(update|publication|delete))?$#D', $requestPath, $portfolioRoute) === 1) {
        $projectId = filter_var($portfolioRoute[1], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => PHP_INT_MAX],
        ]);
        $action = $portfolioRoute[2] ?? '';

        if ($projectId !== false && $action === '' && $requestMethod === 'GET') {
            $portfolioController->edit($projectId);
        }

        if ($projectId !== false && $action === 'update' && $requestMethod === 'POST') {
            $portfolioController->update($projectId);
        }

        if ($projectId !== false && $action === 'publication' && $requestMethod === 'POST') {
            $portfolioController->setPublication($projectId);
        }

        if ($projectId !== false && $action === 'delete' && $requestMethod === 'POST') {
            $portfolioController->delete($projectId);
        }

        if ($projectId !== false) {
            $portfolioController->methodNotAllowed($action === '' ? ['GET'] : ['POST']);
        }
    }

    $adminAccountRoute = [];
    if (preg_match('#^/admin/admins/([1-9][0-9]*)(?:/(password|status))?$#D', $requestPath, $adminAccountRoute) === 1) {
        $adminId = filter_var($adminAccountRoute[1], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => PHP_INT_MAX],
        ]);
        $action = $adminAccountRoute[2] ?? '';
        if ($adminId !== false && $action === '' && $requestMethod === 'GET') {
            $managementController->edit($adminId);
        }
        if ($adminId !== false && $action === 'password' && $requestMethod === 'POST') {
            $managementController->password($adminId);
        }
        if ($adminId !== false && $action === 'status' && $requestMethod === 'POST') {
            $managementController->status($adminId);
        }
        if ($adminId !== false) {
            $managementController->methodNotAllowed($action === '' ? ['GET'] : ['POST']);
        }
    }

    $allowedMethods = match ($requestPath) {
        '/admin/login' => ['GET', 'POST'],
        '/admin/logout' => ['POST'],
        '/admin' => ['GET'],
        '/admin/consultations' => ['GET'],
        '/admin/portfolio' => ['GET'],
        '/admin/portfolio/create' => ['GET', 'POST'],
        '/admin/admins' => ['GET'],
        '/admin/admins/create' => ['GET', 'POST'],
        '/admin/settings' => ['GET', 'POST'],
        default => [],
    };

    if ($allowedMethods !== []) {
        if (in_array($requestPath, ['/admin/login', '/admin/logout'], true)) {
            $authController->methodNotAllowed($allowedMethods);
        }

        if (str_starts_with($requestPath, '/admin/portfolio')) {
            $portfolioController->methodNotAllowed($allowedMethods);
        }
        if (str_starts_with($requestPath, '/admin/admins') || $requestPath === '/admin/settings') {
            $managementController->methodNotAllowed($allowedMethods);
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
$publicConnection = Connection::make($container['database']);
$settingsService = new SiteSettings();
$siteSettings = $settingsService->safePublicValues((new SiteSettingsRepository($publicConnection))->all());
$siteWebsiteHost = (string) parse_url($siteSettings['website_url'], PHP_URL_HOST);
$siteTelephoneHref = $settingsService->telephoneHref($siteSettings['contact_phone']);
$publicPortfolio = new PortfolioRepository($publicConnection);
$portfolioIsTemporary = !$publicPortfolio->hasAny();
$portfolioProjects = $portfolioIsTemporary ? [] : $publicPortfolio->published();

if ($portfolioIsTemporary) {
    $portfolioProjects = TemporaryPortfolio::projects();
} else {
    $telegramValidator = new PortfolioValidator();

    foreach ($portfolioProjects as &$portfolioProject) {
        $telegramUrl = $portfolioProject['telegram_url'] ?? null;

        if (!is_string($telegramUrl) || !$telegramValidator->isTelegramPostUrl($telegramUrl)) {
            $portfolioProject['telegram_url'] = null;
        }

        $imagePath = $portfolioProject['image_path'] ?? null;

        if (
            !is_string($imagePath)
            || preg_match('#^assets/portfolio/uploads/[a-f0-9]{32}\.(?:jpg|png|webp)$#D', $imagePath) !== 1
        ) {
            $portfolioProject['image_path'] = null;
        }
    }

    unset($portfolioProject);
}

header('Cache-Control: no-store');

require $privatePath . '/resources/views/home.php';
