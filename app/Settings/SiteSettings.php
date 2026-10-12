<?php

declare(strict_types=1);

namespace Ara\Settings;

use DomainException;

final class SiteSettings
{
    public const DEFAULTS = [
        'brand_name' => 'ARA Studio',
        'website_url' => 'https://ara-studio.ir',
        'telegram_url' => 'https://t.me/ARA_STUDIO_TM',
        'contact_email' => 'info@ara-studio.ir',
        'contact_phone' => '',
    ];

    /** @param array<string, mixed> $input
     *  @return array<string, string>
     */
    public function validate(array $input): array
    {
        $expected = array_keys(self::DEFAULTS);
        $actual = array_keys($input);
        sort($expected);
        sort($actual);

        if ($actual !== $expected) {
            throw new DomainException('فیلدهای تنظیمات معتبر نیستند.');
        }

        $values = [];
        foreach ($input as $key => $value) {
            if (!is_string($value) || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1) {
                throw new DomainException('مقدار تنظیمات معتبر نیست.');
            }
            $values[$key] = trim($value);
        }

        if ($values['brand_name'] === '' || $this->characterLength($values['brand_name']) > 80 || str_contains($values['brand_name'], "\n")) {
            throw new DomainException('نام برند باید یک خط و حداکثر ۸۰ نویسه باشد.');
        }

        if (!$this->isSafeHttpsUrl($values['website_url'])) {
            throw new DomainException('نشانی وب‌سایت باید یک URL امن HTTPS باشد.');
        }

        if (!$this->isTelegramUrl($values['telegram_url'])) {
            throw new DomainException('نشانی تلگرام باید به یک صفحه معتبر در t.me اشاره کند.');
        }

        if (strlen($values['contact_email']) > 190
            || filter_var($values['contact_email'], FILTER_VALIDATE_EMAIL) === false) {
            throw new DomainException('ایمیل تماس معتبر نیست.');
        }

        if ($values['contact_phone'] !== ''
            && (strlen($values['contact_phone']) > 32
                || preg_match('/^\+?[0-9۰-۹٠-٩ ()\-.]{7,32}$/u', $values['contact_phone']) !== 1)) {
            throw new DomainException('شماره تماس اختیاری معتبر نیست.');
        }

        return $values;
    }

    /** @param array<string, string> $stored
     *  @return array<string, string>
     */
    public function safePublicValues(array $stored): array
    {
        $candidate = array_replace(self::DEFAULTS, array_intersect_key($stored, self::DEFAULTS));

        try {
            return $this->validate($candidate);
        } catch (DomainException) {
            return self::DEFAULTS;
        }
    }

    public function telephoneHref(string $phone): ?string
    {
        if ($phone === '') {
            return null;
        }

        $normalized = strtr($phone, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
        $normalized = preg_replace('/(?!^)\+|[^0-9+]/', '', $normalized);

        return is_string($normalized) && preg_match('/^\+?[0-9]{7,20}$/', $normalized) === 1
            ? 'tel:' . $normalized
            : null;
    }

    private function isSafeHttpsUrl(string $url): bool
    {
        if (strlen($url) > 255 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);
        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && isset($parts['host'])
            && !isset($parts['user'])
            && !isset($parts['pass'])
            && !isset($parts['port'])
            && !isset($parts['query'])
            && !isset($parts['fragment']);
    }

    private function isTelegramUrl(string $url): bool
    {
        if (!$this->isSafeHttpsUrl($url)) {
            return false;
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        return $host === 't.me'
            && !isset($parts['query'])
            && !isset($parts['fragment'])
            && preg_match('#^/[A-Za-z0-9_]{5,32}/?$#D', $path) === 1;
    }

    private function characterLength(string $value): int
    {
        $matched = preg_match_all('/./us', $value, $characters);
        return $matched === false ? PHP_INT_MAX : $matched;
    }
}
