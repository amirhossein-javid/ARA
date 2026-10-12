<?php

declare(strict_types=1);

namespace Ara\Portfolio;

use DomainException;

final class PortfolioValidator
{
    /**
     * @param array<string, mixed> $input
     * @return array{title: string, short_description: string, telegram_url: ?string, display_order: int, is_published: int}
     */
    public function validate(array $input): array
    {
        foreach (['title', 'short_description', 'telegram_url', 'display_order', 'is_published'] as $field) {
            if (!array_key_exists($field, $input) || !is_string($input[$field])) {
                throw new DomainException('اطلاعات پروژه کامل یا معتبر نیست.');
            }
        }

        $title = $this->singleLine($input['title']);
        $description = $this->multiLine($input['short_description']);
        $telegramUrl = trim($input['telegram_url']);
        $displayOrder = filter_var($input['display_order'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0, 'max_range' => 1000000],
        ]);

        if ($this->length($title) < 2 || $this->length($title) > 160) {
            throw new DomainException('عنوان باید بین ۲ تا ۱۶۰ نویسه باشد.');
        }

        if ($this->length($description) < 10 || $this->length($description) > 500) {
            throw new DomainException('توضیح کوتاه باید بین ۱۰ تا ۵۰۰ نویسه باشد.');
        }

        if ($displayOrder === false) {
            throw new DomainException('ترتیب نمایش باید عددی بین صفر تا ۱٬۰۰۰٬۰۰۰ باشد.');
        }

        if (!in_array($input['is_published'], ['0', '1'], true)) {
            throw new DomainException('وضعیت انتشار معتبر نیست.');
        }

        if ($telegramUrl !== '' && !$this->isTelegramPostUrl($telegramUrl)) {
            throw new DomainException('لینک تلگرام باید یک نشانی معتبر HTTPS برای پست t.me باشد.');
        }

        return [
            'title' => $title,
            'short_description' => $description,
            'telegram_url' => $telegramUrl === '' ? null : $telegramUrl,
            'display_order' => $displayOrder,
            'is_published' => (int) $input['is_published'],
        ];
    }

    public function isTelegramPostUrl(string $value): bool
    {
        if (strlen($value) > 500 || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($value);

        if (!is_array($parts)) {
            return false;
        }

        $path = (string) ($parts['path'] ?? '');

        return strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === 't.me'
            && !isset($parts['user'])
            && !isset($parts['pass'])
            && !isset($parts['port'])
            && !isset($parts['query'])
            && !isset($parts['fragment'])
            && preg_match(
                '#^/(?:[A-Za-z0-9_]{4,32}/[1-9][0-9]*(?:/[1-9][0-9]*)?|c/[1-9][0-9]*/[1-9][0-9]*(?:/[1-9][0-9]*)?)/?$#D',
                $path
            ) === 1;
    }

    private function singleLine(string $value): string
    {
        $this->assertText($value);

        return preg_replace('/\s+/u', ' ', trim($value)) ?? '';
    }

    private function multiLine(string $value): string
    {
        $this->assertText($value);

        return trim(str_replace(["\r\n", "\r"], "\n", $value));
    }

    private function assertText(string $value): void
    {
        if (
            preg_match('//u', $value) !== 1
            || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1
        ) {
            throw new DomainException('متن واردشده معتبر نیست.');
        }
    }

    private function length(string $value): int
    {
        $result = preg_match_all('/./us', $value, $matches);

        return $result === false ? PHP_INT_MAX : $result;
    }
}
