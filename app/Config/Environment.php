<?php

declare(strict_types=1);

namespace Ara\Config;

use RuntimeException;

final class Environment
{
    /** @var array<string, string> */
    private static array $values = [];

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            throw new RuntimeException('The environment file could not be read.');
        }

        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            if (!str_contains($line, '=')) {
                throw new RuntimeException(sprintf(
                    'Invalid environment entry on line %d.',
                    $lineNumber + 1
                ));
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);

            if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $name)) {
                throw new RuntimeException(sprintf(
                    'Invalid environment key on line %d.',
                    $lineNumber + 1
                ));
            }

            if (self::existsOutsideFile($name)) {
                continue;
            }

            self::$values[$name] = self::parseValue($value, $lineNumber + 1);
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $serverValue = $_SERVER[$key] ?? null;

        if (is_string($serverValue)) {
            return $serverValue;
        }

        $environmentValue = $_ENV[$key] ?? null;

        if (is_string($environmentValue)) {
            return $environmentValue;
        }

        $processValue = getenv($key);

        if ($processValue !== false) {
            return $processValue;
        }

        return self::$values[$key] ?? $default;
    }

    public static function require(string $key): string
    {
        $value = self::get($key);

        if ($value === null || trim($value) === '') {
            throw new RuntimeException(sprintf('Required environment value %s is missing.', $key));
        }

        return $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        if ($value === null) {
            return $default;
        }

        return match (strtolower(trim($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => throw new RuntimeException(sprintf(
                'Environment value %s must be a boolean.',
                $key
            )),
        };
    }

    private static function existsOutsideFile(string $key): bool
    {
        return array_key_exists($key, $_SERVER)
            || array_key_exists($key, $_ENV)
            || getenv($key) !== false;
    }

    private static function parseValue(string $value, int $lineNumber): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $firstCharacter = $value[0];

        if ($firstCharacter === '"' || $firstCharacter === "'") {
            if (strlen($value) < 2 || substr($value, -1) !== $firstCharacter) {
                throw new RuntimeException(sprintf(
                    'Unclosed quoted environment value on line %d.',
                    $lineNumber
                ));
            }

            $value = substr($value, 1, -1);

            return $firstCharacter === '"'
                ? str_replace(['\\n', '\\r', '\\t', '\\"', '\\\\'], ["\n", "\r", "\t", '"', '\\'], $value)
                : $value;
        }

        $value = preg_replace('/\s+#.*$/', '', $value);

        return trim($value ?? '');
    }
}
