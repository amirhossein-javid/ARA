<?php

declare(strict_types=1);

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

/** @var array{app: array<string, mixed>, database: array<string, mixed>} $container */
$container = require $privatePath . '/bootstrap/app.php';

require $privatePath . '/resources/views/home.php';
