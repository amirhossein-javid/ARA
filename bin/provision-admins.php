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
$inputPath = ARA_BASE_PATH . '/storage/provision/admins.json';

if (!is_file($inputPath)) {
    fwrite(STDERR, "Private provisioning file not found.\n");
    exit(1);
}

if (DIRECTORY_SEPARATOR === '/') {
    $permissions = fileperms($inputPath);

    if ($permissions === false || ($permissions & 0x003F) !== 0) {
        fwrite(STDERR, "Provisioning file permissions must be 0600.\n");
        exit(1);
    }
}

$rawInput = file_get_contents($inputPath);

if ($rawInput === false || strlen($rawInput) > 16384) {
    fwrite(STDERR, "Provisioning file is unavailable or too large.\n");
    exit(1);
}

try {
    $decoded = json_decode($rawInput, true, 16, JSON_THROW_ON_ERROR);
} catch (\JsonException) {
    fwrite(STDERR, "Provisioning file contains invalid JSON.\n");
    exit(1);
}

if (
    !is_array($decoded)
    || count($decoded) !== 1
    || !isset($decoded['admins'])
    || !is_array($decoded['admins'])
    || !array_is_list($decoded['admins'])
) {
    fwrite(STDERR, "Provisioning file structure is invalid.\n");
    exit(1);
}

$connection = Connection::make($container['database']);
$service = new AdminAccountService(
    $connection,
    new AdminRepository($connection),
    $container['auth']['minimum_password_length']
);

try {
    $service->provisionExactlyTwo($decoded['admins']);
} catch (\DomainException $exception) {
    fwrite(STDERR, $exception->getMessage() . "\n");
    exit(1);
}

$cleared = @file_put_contents($inputPath, '', LOCK_EX);
$removed = $cleared !== false && @unlink($inputPath);

if (!$removed) {
    fwrite(STDERR, "Accounts were created, but the private provisioning file must be deleted manually.\n");
    exit(2);
}

fwrite(STDOUT, "Exactly two administrator accounts were created. Provisioning is now locked.\n");
