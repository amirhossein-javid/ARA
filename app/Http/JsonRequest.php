<?php

declare(strict_types=1);

namespace Ara\Http;

use JsonException;
use stdClass;

final class JsonRequest
{
    /** @return array<string, mixed> */
    public static function read(int $maximumBytes): array
    {
        $contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));

        if ($contentType !== 'application/json') {
            throw new HttpException(415, 'unsupported_media_type', 'نوع محتوای درخواست پشتیبانی نمی‌شود.');
        }

        $contentLength = filter_var(
            $_SERVER['CONTENT_LENGTH'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );

        if ($contentLength !== false && $contentLength !== null && $contentLength > $maximumBytes) {
            throw new HttpException(413, 'payload_too_large', 'حجم درخواست بیشتر از حد مجاز است.');
        }

        $stream = fopen('php://input', 'rb');

        if ($stream === false) {
            throw new \RuntimeException('The request body could not be opened.');
        }

        try {
            $body = stream_get_contents($stream, $maximumBytes + 1);
        } finally {
            fclose($stream);
        }

        if ($body === false) {
            throw new \RuntimeException('The request body could not be read.');
        }

        if (strlen($body) > $maximumBytes) {
            throw new HttpException(413, 'payload_too_large', 'حجم درخواست بیشتر از حد مجاز است.');
        }

        if ($body === '') {
            throw new HttpException(400, 'invalid_json', 'بدنه درخواست معتبر نیست.');
        }

        try {
            $decoded = json_decode($body, false, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new HttpException(400, 'invalid_json', 'ساختار JSON معتبر نیست.');
        }

        if (!$decoded instanceof stdClass) {
            throw new HttpException(400, 'invalid_json', 'بدنه درخواست باید یک شیء JSON باشد.');
        }

        return get_object_vars($decoded);
    }
}
