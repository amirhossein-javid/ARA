<?php

declare(strict_types=1);

namespace Ara\Portfolio;

final class TemporaryPortfolio
{
    /** @return list<array<string, mixed>> */
    public static function projects(): array
    {
        return [
            self::item('نمونه آزمایشی وب‌سایت شرکتی', 'نمونه‌ای موقت برای بررسی ارائه خدمات، معرفی برند و ساختار محتوای شرکتی.', 'assets/portfolio/placeholder-corporate.svg', 'وب‌سایت شرکتی'),
            self::item('نمونه آزمایشی فروشگاه آنلاین', 'نمونه‌ای موقت برای ارزیابی نمایش محصول، مسیر خرید و تجربه واکنش‌گرا.', 'assets/portfolio/placeholder-store.svg', 'وب‌سایت فروشگاهی'),
            self::item('نمونه آزمایشی لندینگ‌پیج', 'نمونه‌ای موقت با تمرکز بر پیام واحد، سلسله‌مراتب روشن و اقدام اصلی.', 'assets/portfolio/placeholder-landing.svg', 'لندینگ‌پیج'),
            self::item('نمونه آزمایشی وب‌سایت فناوری', 'نمونه‌ای موقت برای بررسی معرفی محصول فنی و سازمان‌دهی اطلاعات پیچیده.', 'assets/portfolio/placeholder-brand.svg', 'وب‌سایت فناوری'),
            self::item('نمونه آزمایشی وب‌سایت پزشکی', 'نمونه‌ای موقت برای سنجش خوانایی، آرامش بصری و دسترسی سریع به اطلاعات.', 'assets/portfolio/placeholder-product.svg', 'وب‌سایت پزشکی'),
            self::item('نمونه آزمایشی استودیو خلاق', 'نمونه‌ای موقت برای ارزیابی ترکیب‌بندی جسور، روایت تصویری و معرفی پروژه‌ها.', 'assets/portfolio/placeholder-special.svg', 'استودیو خلاق'),
        ];
    }

    /** @return array<string, mixed> */
    private static function item(string $title, string $description, string $imagePath, string $label): array
    {
        return [
            'id' => null,
            'title' => $title,
            'short_description' => $description,
            'image_path' => $imagePath,
            'telegram_url' => null,
            'display_order' => 0,
            'label' => $label,
            'is_placeholder' => true,
        ];
    }
}
