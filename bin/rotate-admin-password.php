<?php

declare(strict_types=1);

use Ara\Auth\AdminAccountService;
use Ara\Auth\AdminRepository;
use Ara\Database\Connection;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/** @var array{app: array<string, mixed>, database: array<string, mixed>, auth: array<string, mixed>} $container */
$container = require dirname(__DIR__) . '/bootstrap/app.php';
$inputPath = ARA_BASE_PATH . '/storage/provision/password-rotation.json';

if (!is_file($inputPath)) {
    fwrite(STDERR, "Private password-rotation file not found.\n");
    exit(1);
}

if (DIRECTORY_SEPARATOR === '/') {
    $permissions = fileperms($inputPath);

    if ($permissions === false || ($permissions & 0x003F) !== 0) {
        fwrite(STDERR, "Password-rotation file permissions must be 0600.\n");
        exit(1);
    }
}

$rawInput = file_get_contents($inputPath);

if ($rawInput === false || strlen($rawInput) > 8192) {
    fwrite(STDERR, "Password-rotation file is unavailable or too large.\n");
    exit(1);
}

try {
    $decoded = json_decode($rawInput, true, 8, JSON_THROW_ON_ERROR);
} catch (\JsonException) {
    fwrite(STDERR, "Password-rotation file contains invalid JSON.\n");
    exit(1);
}

$expectedFields = ['current_password', 'new_password', 'username'];

if (!is_array($decoded) || array_diff(array_keys($decoded), $expectedFields) !== []) {
    fwrite(STDERR, "Password-rotation file structure is invalid.\n");
    exit(1);
}

foreach ($expectedFields as $field) {
    if (!array_key_exists($field, $decoded) || !is_string($decoded[$field])) {
        fwrite(STDERR, "Password-rotation file structure is invalid.\n");
        exit(1);
    }
}

$connection = Connection::make($container['database']);
$service = new AdminAccountService(
    $connection,
    new AdminRepository($connection),
    $container['auth']['minimum_password_length']
);

try {
    $service->rotatePassword(
        $decoded['username'],
        $decoded['current_password'],
        $decoded['new_password']
    );
} catch (\DomainException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

$cleared = @file_put_contents($inputPath, '', LOCK_EX);
$removed = $cleared !== false && @unlink($inputPath);

if (!$removed) {
    fwrite(STDERR, "Password was rotated, but the private rotation file must be deleted manually.\n");
    exit(2);
}

fwrite(STDOUT, "Administrator password was rotated successfully.\n");
