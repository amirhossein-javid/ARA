<?php

declare(strict_types=1);

namespace Ara\Seo;

final class PublicSeo
{
    /** @param array<string, string> $seo @param array<string, string> $site */
    public function __construct(private readonly array $seo, private readonly array $site)
    {
    }

    public function robotsDirective(): string
    {
        return ($this->seo['robots_index'] === '1' ? 'index' : 'noindex')
            . ',' . ($this->seo['robots_follow'] === '1' ? 'follow' : 'nofollow');
    }

    public function socialImageUrl(): string
    {
        return rtrim($this->site['website_url'], '/') . '/' . ltrim($this->seo['social_image_path'], '/');
    }

    /** @return array<string, mixed> */
    public function structuredData(?string $telephoneHref): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->site['brand_name'],
            'url' => $this->site['website_url'],
            'logo' => rtrim($this->site['website_url'], '/') . '/assets/ara-logo.png',
            'email' => 'mailto:' . $this->site['contact_email'],
            'sameAs' => [$this->site['telegram_url']],
        ];
        if ($telephoneHref !== null) {
            $data['telephone'] = substr($telephoneHref, 4);
        }
        return $data;
    }

    public function robotsText(): string
    {
        return "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /api/\n"
            . 'Sitemap: ' . rtrim($this->site['website_url'], '/') . "/sitemap.xml\n";
    }

    public function sitemapXml(): string
    {
        $entry = '';
        if ($this->seo['robots_index'] === '1') {
            $url = htmlspecialchars($this->seo['canonical_url'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $entry = "  <url><loc>{$url}</loc></url>\n";
        }
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . $entry
            . "</urlset>\n";
    }
}
