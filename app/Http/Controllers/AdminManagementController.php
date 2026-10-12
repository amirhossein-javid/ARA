<?php

declare(strict_types=1);

namespace Ara\Http\Controllers;

use Ara\Auth\AdminAccess;
use Ara\Auth\AdminAccountService;
use Ara\Auth\AdminRepository;
use Ara\Auth\AuthSession;
use Ara\Security\Csrf;
use Ara\Security\SameOrigin;
use Ara\Security\Session;
use Ara\Settings\SiteSettings;
use Ara\Settings\SiteSettingsRepository;
use DomainException;

final class AdminManagementController
{
    private readonly AdminAccess $access;

    /** @param array<string, mixed> $app @param array<string, mixed> $database @param array<string, mixed> $auth */
    public function __construct(
        private readonly array $app,
        array $database,
        private readonly array $auth,
        private readonly string $privatePath
    ) {
        $this->access = new AdminAccess($app, $database, $auth);
    }

    public function admins(): never
    {
        $admin = $this->requireAdmin();
        $admins = $this->repository()->all();
        $notice = $this->notice(['created', 'password', 'activated', 'deactivated']);
        $this->render('admins.php', $admin, compact('admins', 'notice'), 'admins');
    }

    public function createForm(?string $error = null, string $username = ''): never
    {
        $admin = $this->requireAdmin();
        $this->render('admin-create.php', $admin, compact('error', 'username'), 'admins', $error === null ? 200 : 422);
    }

    public function create(): never
    {
        $admin = $this->requireAdmin();
        $this->validatePost(['csrf_token', 'current_password', 'new_password', 'new_password_confirmation', 'username']);
        $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';

        if (!is_string($_POST['new_password_confirmation'] ?? null)
            || $_POST['new_password_confirmation'] !== ($_POST['new_password'] ?? null)) {
            $this->createForm('تکرار رمز عبور با رمز جدید یکسان نیست.', $username);
        }

        try {
            $this->service()->createAccount(
                $admin['id'],
                $_POST['username'] ?? null,
                $_POST['new_password'] ?? null,
                $_POST['current_password'] ?? null
            );
        } catch (DomainException $exception) {
            $this->createForm($this->localizedAccountError($exception), $username);
        }

        $this->redirect('/admin/admins?updated=created');
    }

    public function edit(int $adminId, ?string $error = null): never
    {
        $admin = $this->requireAdmin();
        $record = $this->repository()->findById($adminId);

        if ($record === null) {
            $this->fail(404, 'حساب مدیر پیدا نشد.');
        }

        unset($record['password_hash']);
        $notice = $this->notice(['password', 'activated', 'deactivated']);
        $this->render('admin-edit.php', $admin, compact('record', 'notice', 'error'), 'admins', $error === null ? 200 : 422);
    }

    public function password(int $adminId): never
    {
        $admin = $this->requireAdmin();
        $this->validatePost(['csrf_token', 'current_password', 'new_password', 'new_password_confirmation']);

        if (!is_string($_POST['new_password_confirmation'] ?? null)
            || $_POST['new_password_confirmation'] !== ($_POST['new_password'] ?? null)) {
            $this->edit($adminId, 'تکرار رمز عبور با رمز جدید یکسان نیست.');
        }

        try {
            $changedSelf = $this->service()->changePassword(
                $admin['id'],
                $adminId,
                $_POST['current_password'] ?? null,
                $_POST['new_password'] ?? null
            );
        } catch (DomainException $exception) {
            $this->edit($adminId, $this->localizedAccountError($exception));
        }

        if ($changedSelf) {
            AuthSession::logout();
            $this->redirect('/admin/login');
        }

        $this->redirect('/admin/admins/' . $adminId . '?updated=password');
    }

    public function status(int $adminId): never
    {
        $admin = $this->requireAdmin();
        $this->validatePost(['csrf_token', 'current_password', 'is_active']);
        $activeValue = $_POST['is_active'] ?? null;

        if (!is_string($activeValue) || !in_array($activeValue, ['0', '1'], true)) {
            $this->fail(422, 'وضعیت حساب معتبر نیست.');
        }

        try {
            $this->service()->setActive(
                $admin['id'],
                $adminId,
                $activeValue === '1',
                $_POST['current_password'] ?? null
            );
        } catch (DomainException $exception) {
            $this->edit($adminId, $this->localizedAccountError($exception));
        }

        $this->redirect('/admin/admins/' . $adminId . '?updated=' . ($activeValue === '1' ? 'activated' : 'deactivated'));
    }

    public function settings(): never
    {
        $admin = $this->requireAdmin();
        $settings = (new SiteSettings())->safePublicValues($this->settingsRepository()->all());
        $notice = $this->notice(['saved']);
        $error = null;
        $this->render('settings.php', $admin, compact('settings', 'notice', 'error'), 'settings');
    }

    public function saveSettings(): never
    {
        $admin = $this->requireAdmin();
        $fields = ['brand_name', 'contact_email', 'contact_phone', 'csrf_token', 'telegram_url', 'website_url'];
        $this->validatePost($fields);
        $input = array_intersect_key($_POST, SiteSettings::DEFAULTS);

        try {
            $settings = (new SiteSettings())->validate($input);
            $this->settingsRepository()->save($settings, $admin['id']);
        } catch (DomainException $exception) {
            $settings = array_replace(SiteSettings::DEFAULTS, array_map(
                static fn (mixed $value): string => is_string($value) ? $value : '',
                $input
            ));
            $error = $exception->getMessage();
            $notice = null;
            $this->render('settings.php', $admin, compact('settings', 'notice', 'error'), 'settings', 422);
        }

        $this->redirect('/admin/settings?updated=saved');
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

    /** @param list<string> $expected */
    private function validatePost(array $expected): void
    {
        $type = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
        $length = filter_var($_SERVER['CONTENT_LENGTH'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $actual = array_keys($_POST);
        sort($actual);
        sort($expected);

        if ($type !== 'application/x-www-form-urlencoded' || !is_int($length)
            || $length > max(16384, (int) $this->auth['request_max_bytes']) || $actual !== $expected) {
            $this->fail(422, 'ساختار فرم معتبر نیست.');
        }

        if (!SameOrigin::accepts($this->app['url'])
            || !is_string($_POST['csrf_token'] ?? null)
            || !Csrf::verify($_POST['csrf_token'])) {
            $this->fail(403, 'اعتبار درخواست تأیید نشد. صفحه را تازه‌سازی کنید.');
        }
    }

    private function service(): AdminAccountService
    {
        return new AdminAccountService($this->access->connection(), $this->repository(), $this->auth['minimum_password_length']);
    }

    private function repository(): AdminRepository
    {
        return new AdminRepository($this->access->connection());
    }

    private function settingsRepository(): SiteSettingsRepository
    {
        return new SiteSettingsRepository($this->access->connection());
    }

    private function localizedAccountError(DomainException $exception): string
    {
        $message = $exception->getMessage();
        if (str_contains($message, 'at least')) {
            return 'رمز عبور باید حداقل ' . (int) $this->auth['minimum_password_length'] . ' نویسه داشته باشد.';
        }
        if (str_contains($message, 'lowercase ASCII')) {
            return 'نام کاربری باید ۳ تا ۶۴ نویسه و شامل حروف کوچک انگلیسی، عدد، نقطه، خط تیره یا زیرخط باشد.';
        }
        if (str_contains($message, 'cannot equal')) {
            return 'رمز عبور نمی‌تواند با نام کاربری یکسان باشد.';
        }
        $safeMessages = [
            'امکان ایجاد این حساب وجود ندارد.',
            'امکان تأیید این تغییر وجود ندارد.',
            'حساب مدیر پیدا نشد.',
            'رمز عبور جدید باید با رمز فعلی متفاوت باشد.',
            'نمی‌توانید حسابی را که با آن وارد شده‌اید غیرفعال کنید.',
            'آخرین مدیر فعال را نمی‌توان غیرفعال کرد.',
            'وضعیت حساب تغییر نکرد.',
        ];
        return in_array($message, $safeMessages, true) ? $message : 'امکان انجام این تغییر وجود ندارد.';
    }

    private function notice(array $allowed): ?string
    {
        $notice = $_GET['updated'] ?? null;
        return is_string($notice) && in_array($notice, $allowed, true) ? $notice : null;
    }

    /** @param array<string, mixed> $variables */
    private function render(string $view, array $admin, array $variables, string $activeNavigation, int $status = 200): never
    {
        http_response_code($status);
        extract($variables, EXTR_SKIP);
        $adminUsername = $admin['username'];
        $currentAdminId = $admin['id'];
        $csrfToken = Csrf::token();
        require $this->privatePath . '/resources/views/admin/' . $view;
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
        echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>خطای مدیریت</title><body style="font-family:system-ui,sans-serif;background:#0b0c0e;color:#f1f3f5;padding:2rem;line-height:2"><main><h1>امکان انجام درخواست وجود ندارد</h1><p>' . e($message) . '</p><a style="color:#d7dbe0" href="/admin">بازگشت به داشبورد</a></main></body></html>';
        exit;
    }

    private function redirect(string $location): never
    {
        if (preg_match('#^/admin(?:/login|/admins(?:/[1-9][0-9]*)?|/settings)?(?:\?updated=(?:created|password|activated|deactivated|saved))?$#D', $location) !== 1) {
            throw new \LogicException('Unsafe administration redirect target.');
        }
        header('Location: ' . $location, true, 303);
        exit;
    }
}
