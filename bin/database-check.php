<?php

declare(strict_types=1);

use Ara\Database\Connection;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/** @var array{app: array<string, mixed>, database: array<string, mixed>} $container */
$container = require dirname(__DIR__) . '/bootstrap/app.php';
$connection = Connection::make($container['database']);
$statement = $connection->prepare('SELECT 1 AS connection_ok');
$statement->execute();
$result = $statement->fetchColumn();

if ((int) $result !== 1) {
    throw new RuntimeException('The database connection check returned an unexpected result.');
}

fwrite(STDOUT, "Database connection succeeded.\n");
