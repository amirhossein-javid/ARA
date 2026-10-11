<?php

declare(strict_types=1);

namespace Ara\Database;

use InvalidArgumentException;
use PDO;

final class Connection
{
    /**
     * @param array{
     *     host: string,
     *     port: int,
     *     database: string,
     *     username: string,
     *     password: string,
     *     charset: string
     * } $config
     */
    public static function make(array $config): PDO
    {
        self::validateConfig($config);

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
        ]);
    }

    /** @param array<string, mixed> $config */
    public static function validateConfig(array $config): void
    {
        foreach (['host', 'database', 'username', 'password', 'charset', 'port'] as $key) {
            if (!array_key_exists($key, $config)) {
                throw new InvalidArgumentException(sprintf('Database configuration key %s is missing.', $key));
            }
        }

        foreach (['host', 'database', 'username'] as $key) {
            if (!is_string($config[$key]) || trim($config[$key]) === '') {
                throw new InvalidArgumentException(sprintf('Database configuration key %s cannot be empty.', $key));
            }

            if (preg_match('/[;\x00-\x1F\x7F]/', $config[$key])) {
                throw new InvalidArgumentException(sprintf('Database configuration key %s is invalid.', $key));
            }
        }

        if (!is_string($config['password'])) {
            throw new InvalidArgumentException('Database password must be a string.');
        }

        if (!is_int($config['port']) || $config['port'] < 1 || $config['port'] > 65535) {
            throw new InvalidArgumentException('Database port is invalid.');
        }

        if ($config['charset'] !== 'utf8mb4') {
            throw new InvalidArgumentException('The database connection must use utf8mb4.');
        }
    }
}
