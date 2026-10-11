<?php

declare(strict_types=1);

namespace Ara\Http;

use ErrorException;
use Throwable;

final class ErrorHandler
{
    private static string $environment = 'production';
    private static string $logFile = '';
    private static bool $debug = false;
    private static bool $registered = false;
    private static bool $handling = false;

    public static function register(string $environment, string $logFile, bool $debug = false): void
    {
        self::$environment = $environment;
        self::$logFile = $logFile;
        self::$debug = $debug;

        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('log_errors', '0');
        ini_set('zend.exception_ignore_args', '1');
        header_remove('X-Powered-By');

        if (self::$registered) {
            return;
        }

        set_error_handler(static function (
            int $severity,
            string $message,
            string $file,
            int $line
        ): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler([self::class, 'handle']);
        register_shutdown_function([self::class, 'handleShutdown']);
        self::$registered = true;
    }

    public static function handle(Throwable $throwable): void
    {
        if (self::$handling) {
            return;
        }

        self::$handling = true;
        $requestId = self::requestId();
        self::writeLog($throwable, $requestId);

        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, sprintf("Application error. Reference: %s\n", $requestId));
            exit(1);
        }

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
            header('Cache-Control: no-store');
        }

        $developmentNote = self::$environment === 'production' || !self::$debug
            ? ''
            : '<p>جزئیات فنی در لاگ محلی ثبت شد.</p>';

        echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>خطای موقت</title><body style="font-family:system-ui,sans-serif;padding:2rem;line-height:1.8">'
            . '<h1>خطای موقت</h1><p>در پردازش درخواست مشکلی رخ داد. لطفاً دوباره تلاش کنید.</p>'
            . $developmentNote
            . '<p><small>شناسه پیگیری: ' . htmlspecialchars($requestId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</small></p>'
            . '</body></html>';

        self::$handling = false;
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        self::handle(new ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        ));
    }

    private static function writeLog(Throwable $throwable, string $requestId): void
    {
        if (self::$logFile === '') {
            return;
        }

        $directory = dirname(self::$logFile);

        if (!is_dir($directory) || !is_writable($directory)) {
            return;
        }

        $message = self::redact($throwable->getMessage());
        $trace = self::redact($throwable->getTraceAsString());
        $entry = sprintf(
            "[%s] %s %s: %s in %s:%d\n%s\n",
            gmdate('c'),
            $requestId,
            $throwable::class,
            $message,
            $throwable->getFile(),
            $throwable->getLine(),
            $trace
        );

        @file_put_contents(self::$logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    private static function redact(string $value): string
    {
        $value = preg_replace(
            '/\b(password|passwd|pwd|secret|token|authorization)\b\s*[:=]\s*[^\s;,]+/i',
            '$1=[REDACTED]',
            $value
        ) ?? '[redacted]';

        return preg_replace('/mysql:[^\s]+/i', 'mysql:[REDACTED]', $value) ?? '[redacted]';
    }

    private static function requestId(): string
    {
        try {
            return bin2hex(random_bytes(8));
        } catch (Throwable) {
            return substr(hash('sha256', uniqid('', true)), 0, 16);
        }
    }
}
