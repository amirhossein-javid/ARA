<?php

declare(strict_types=1);

namespace Ara\Seo;

use DomainException;

final class SeoSettings
{
    public const DEFAULTS = [
        'homepage_title' => 'ARA Studio | طراحی تجربه‌های دیجیتال متمایز',
        'meta_description' => 'ARA Studio؛ طراحی وب‌سایت، رابط کاربری و هویت بصری با رویکرد مشاوره‌محور.',
        'canonical_url' => 'https://ara-studio.ir/',
        'og_title' => 'ARA Studio | طراحی تجربه‌های دیجیتال متمایز',
        'og_description' => 'طراحی وب‌سایت، رابط کاربری و هویت بصری با رویکرد مشاوره‌محور.',
        'twitter_card' => 'summary_large_image',
        'twitter_title' => 'ARA Studio | طراحی تجربه‌های دیجیتال متمایز',
        'twitter_description' => 'طراحی وب‌سایت، رابط کاربری و هویت بصری با رویکرد مشاوره‌محور.',
        'robots_index' => '1',
        'robots_follow' => '1',
        'google_verification' => '',
        'bing_verification' => '',
        'social_image_path' => 'assets/ara-logo.png',
    ];

    /** @param array<string, mixed> $input @return array<string, string> */
    public function validate(array $input, string $websiteUrl): array
    {
        $expected = array_keys(self::DEFAULTS);
        $actual = array_keys($input);
        sort($expected);
        sort($actual);

        if ($actual !== $expected) {
            throw new DomainException('فیلدهای تنظیمات سئو معتبر نیستند.');
        }

        $values = [];
        foreach ($input as $key => $value) {
            if (!is_string($value) || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1) {
                throw new DomainException('مقدار تنظیمات سئو معتبر نیست.');
            }
            $values[$key] = trim($value);
        }

        $this->validateText($values['homepage_title'], 10, 70, 'عنوان سئوی صفحه');
        $this->validateText($values['meta_description'], 30, 170, 'توضیحات متا');
        $this->validateText($values['og_title'], 10, 95, 'عنوان Open Graph');
        $this->validateText($values['og_description'], 20, 200, 'توضیحات Open Graph');
        $this->validateText($values['twitter_title'], 10, 70, 'عنوان Twitter/X');
        $this->validateText($values['twitter_description'], 20, 200, 'توضیحات Twitter/X');

        if (!$this->isCanonicalUrl($values['canonical_url'], $websiteUrl)) {
            throw new DomainException('نشانی canonical باید HTTPS، بدون query و fragment و روی دامنه وب‌سایت باشد.');
        }

        if (!in_array($values['twitter_card'], ['summary', 'summary_large_image'], true)) {
            throw new DomainException('نوع کارت Twitter/X معتبر نیست.');
        }

        foreach (['robots_index', 'robots_follow'] as $key) {
            if (!in_array($values[$key], ['0', '1'], true)) {
                throw new DomainException('تنظیمات robots معتبر نیست.');
            }
        }

        foreach (['google_verification', 'bing_verification'] as $key) {
            if ($values[$key] !== ''
                && (strlen($values[$key]) > 255
                    || preg_match('/^[A-Za-z0-9._=-]{5,255}$/D', $values[$key]) !== 1)) {
                throw new DomainException('توکن تأیید موتور جست‌وجو معتبر نیست.');
            }
        }

        if (!$this->isSocialImagePath($values['social_image_path'])) {
            throw new DomainException('مسیر تصویر اشتراک‌گذاری معتبر نیست.');
        }

        return $values;
    }

    /** @param array<string, string> $stored @return array<string, string> */
    public function safePublicValues(array $stored, string $websiteUrl): array
    {
        $candidate = array_replace(self::DEFAULTS, array_intersect_key($stored, self::DEFAULTS));
        try {
            return $this->validate($candidate, $websiteUrl);
        } catch (DomainException) {
            $fallback = self::DEFAULTS;
            $fallback['canonical_url'] = rtrim($websiteUrl, '/') . '/';
            return $fallback;
        }
    }

    public function isSocialImagePath(string $path): bool
    {
        return $path === 'assets/ara-logo.png'
            || preg_match('#^assets/seo/uploads/[a-f0-9]{32}\.(?:jpg|png|webp)$#D', $path) === 1;
    }

    private function validateText(string $value, int $minimum, int $maximum, string $label): void
    {
        $length = preg_match_all('/./us', $value, $characters);
        if ($length === false || $length < $minimum || $length > $maximum || str_contains($value, "\n")) {
            throw new DomainException($label . ' از نظر طول یا ساختار معتبر نیست.');
        }
    }

    private function isCanonicalUrl(string $url, string $websiteUrl): bool
    {
        if (strlen($url) > 255 || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        $parts = parse_url($url);
        $website = parse_url($websiteUrl);
        return is_array($parts) && is_array($website)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === strtolower((string) ($website['host'] ?? ''))
            && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['port'])
            && !isset($parts['query']) && !isset($parts['fragment']);
    }
}
