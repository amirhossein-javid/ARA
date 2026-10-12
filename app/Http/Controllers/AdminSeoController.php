<?php

declare(strict_types=1);

namespace Ara\Http\Controllers;

use Ara\Auth\AdminAccess;
use Ara\Security\Csrf;
use Ara\Security\SameOrigin;
use Ara\Security\Session;
use Ara\Seo\SeoImageStorage;
use Ara\Seo\SeoSettings;
use Ara\Seo\SeoSettingsRepository;
use Ara\Settings\SiteSettings;
use Ara\Settings\SiteSettingsRepository;
use DomainException;
use Throwable;

final class AdminSeoController
{
    private readonly AdminAccess $access;
    private readonly SeoImageStorage $images;

    /** @param array<string, mixed> $app @param array<string, mixed> $database @param array<string, mixed> $auth @param array<string, mixed> $seo */
    public function __construct(
        private readonly array $app,
        array $database,
        array $auth,
        private readonly array $seo,
        private readonly string $privatePath,
        string $publicPath
    ) {
        $this->access = new AdminAccess($app, $database, $auth);
        $this->images = new SeoImageStorage($publicPath, $seo);
    }

    public function show(?string $error = null, ?array $submitted = null): never
    {
        $admin = $this->requireAdmin();
        $siteSettings = $this->siteSettings();
        $settings = $submitted ?? (new SeoSettings())->safePublicValues($this->repository()->get(), $siteSettings['website_url']);
        $notice = $this->notice();
        $this->render($admin, compact('settings', 'notice', 'error'), $error === null ? 200 : 422);
    }

    public function save(): never
    {
        $admin = $this->requireAdmin();
        $required = [
            'bing_verification', 'canonical_url', 'csrf_token', 'google_verification', 'homepage_title',
            'meta_description', 'og_description', 'og_title', 'robots_follow', 'robots_index',
            'twitter_card', 'twitter_description', 'twitter_title',
        ];
        $this->validateMultipart($required, ['remove_image']);
        $siteSettings = $this->siteSettings();
        $existing = (new SeoSettings())->safePublicValues($this->repository()->get(), $siteSettings['website_url']);
        $input = [];
        foreach (array_keys(SeoSettings::DEFAULTS) as $key) {
            if ($key !== 'social_image_path') {
                $input[$key] = $_POST[$key] ?? null;
            }
        }
        $input['social_image_path'] = $existing['social_image_path'];
        $removeImage = ($_POST['remove_image'] ?? null) === 'yes';
        if (isset($_POST['remove_image']) && !$removeImage) {
            $this->fail(422, 'درخواست بازنشانی تصویر معتبر نیست.');
        }

        $newImagePath = null;
        try {
            $validator = new SeoSettings();
            $data = $validator->validate($input, $siteSettings['website_url']);
            $newImagePath = $this->images->store($this->imageFile());
            if ($newImagePath !== null && $removeImage) {
                throw new DomainException('برای جایگزینی تصویر، گزینه بازنشانی را هم‌زمان انتخاب نکنید.');
            }
            $data['social_image_path'] = $newImagePath
                ?? ($removeImage ? SeoSettings::DEFAULTS['social_image_path'] : $existing['social_image_path']);
            $data = $validator->validate($data, $siteSettings['website_url']);
            $this->repository()->save($data, $admin['id']);
        } catch (DomainException $exception) {
            $this->images->delete($newImagePath);
            $submitted = array_replace($existing, array_map(
                static fn (mixed $value): string => is_string($value) ? $value : '',
                $input
            ));
            $this->show($exception->getMessage(), $submitted);
        } catch (Throwable $throwable) {
            $this->images->delete($newImagePath);
            throw $throwable;
        }

        if ($data['social_image_path'] !== $existing['social_image_path']) {
            $this->images->delete($existing['social_image_path']);
        }
        $this->redirect('/admin/seo?updated=saved');
    }

    /** @param list<string> $allowedMethods */
    public function methodNotAllowed(array $allowedMethods): never
    {
        $this->requireAdmin();
        http_response_code(405);
        header('Allow: ' . implode(', ', $allowedMethods));
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Method not allowed.';
        exit;
    }

    /** @return array{id: int, username: string} */
    private function requireAdmin(): array
    {
        $this->noStore();
        if ($this->app['environment'] === 'production' && !Session::isSecureRequest()) {
            $this->fail(400, 'برای ورود به مدیریت استفاده از HTTPS الزامی است.');
        }
        $admin = $this->access->currentAdmin();
        if ($admin === null) {
            $this->redirect('/admin/login');
        }
        return $admin;
    }

    /** @param list<string> $required @param list<string> $optional */
    private function validateMultipart(array $required, array $optional): void
    {
        $type = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
        $length = filter_var($_SERVER['CONTENT_LENGTH'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $actual = array_keys($_POST);
        $allowed = array_merge($required, $optional);
        if ($type !== 'multipart/form-data' || !is_int($length) || $length > $this->seo['request_max_bytes']
            || array_diff($actual, $allowed) !== [] || array_diff($required, $actual) !== []
            || array_diff(array_keys($_FILES), ['image']) !== []) {
            $this->fail(422, 'ساختار یا اندازه فرم معتبر نیست.');
        }
        if (!SameOrigin::accepts($this->app['url'])
            || !is_string($_POST['csrf_token'] ?? null) || !Csrf::verify($_POST['csrf_token'])) {
            $this->fail(403, 'اعتبار درخواست تأیید نشد. صفحه را تازه‌سازی کنید.');
        }
    }

    /** @return array<string, mixed>|null */
    private function imageFile(): ?array
    {
        $file = $_FILES['image'] ?? null;
        return is_array($file) ? $file : null;
    }

    /** @return array<string, string> */
    private function siteSettings(): array
    {
        $stored = (new SiteSettingsRepository($this->access->connection()))->all();
        return (new SiteSettings())->safePublicValues($stored);
    }

    private function repository(): SeoSettingsRepository
    {
        return new SeoSettingsRepository($this->access->connection());
    }

    private function notice(): ?string
    {
        return ($_GET['updated'] ?? null) === 'saved' ? 'saved' : null;
    }

    /** @param array<string, mixed> $variables */
    private function render(array $admin, array $variables, int $status): never
    {
        http_response_code($status);
        extract($variables, EXTR_SKIP);
        $adminUsername = $admin['username'];
        $csrfToken = Csrf::token();
        $activeNavigation = 'seo';
        require $this->privatePath . '/resources/views/admin/seo.php';
        exit;
    }

    private function noStore(): void
    {
        header('Cache-Control: no-store, private, max-age=0');
        header('Pragma: no-cache');
        header('X-Robots-Tag: noindex, nofollow');
    }

    private function fail(int $status, string $message): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><meta name="robots" content="noindex,nofollow"><title>خطای مدیریت</title><body style="font-family:system-ui,sans-serif;background:#0b0c0e;color:#f1f3f5;padding:2rem;line-height:2"><h1>امکان انجام درخواست وجود ندارد</h1><p>' . e($message) . '</p><a style="color:#d7dbe0" href="/admin/seo">بازگشت به سئو</a></body></html>';
        exit;
    }

    private function redirect(string $location): never
    {
        if (preg_match('#^/admin/(?:login|seo(?:\?updated=saved)?)$#D', $location) !== 1) {
            throw new \LogicException('Unsafe SEO redirect target.');
        }
        header('Location: ' . $location, true, 303);
        exit;
    }
}
