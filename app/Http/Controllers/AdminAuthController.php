<?php

declare(strict_types=1);

namespace Ara\Http\Controllers;

use Ara\Auth\AdminRepository;
use Ara\Auth\Authenticator;
use Ara\Auth\AuthSession;
use Ara\Database\Connection;
use Ara\Security\Csrf;
use Ara\Security\RateLimiter;
use Ara\Security\SameOrigin;
use Ara\Security\Session;

final class AdminAuthController
{
    /**
     * @param array<string, mixed> $app
     * @param array<string, mixed> $database
     * @param array<string, mixed> $configuration
     */
    public function __construct(
        private readonly array $app,
        private readonly array $database,
        private readonly array $configuration,
        private readonly string $privatePath
    ) {
    }

    public function showLogin(): never
    {
        $this->requireSecureTransport();
        $this->noStore();

        if ($this->currentAdminId() !== null) {
            $this->redirect('/admin');
        }

        $this->renderLogin(null, '', 200);
    }

    public function login(): never
    {
        $this->requireSecureTransport();
        $this->noStore();

        if ($this->currentAdminId() !== null) {
            $this->redirect('/admin');
        }

        if (!$this->isValidFormRequest()) {
            $this->renderLogin('درخواست ورود معتبر نیست.', '', 415);
        }

        if (!SameOrigin::accepts($this->app['url'])) {
            $this->renderLogin('اعتبار درخواست ورود تأیید نشد.', '', 403);
        }

        $csrfToken = $_POST['csrf_token'] ?? null;

        if (!is_string($csrfToken) || !Csrf::verify($csrfToken)) {
            $this->renderLogin('نشست منقضی شده است. صفحه را تازه‌سازی کنید.', '', 403);
        }

        $expectedFields = ['csrf_token', 'password', 'username'];

        if (array_diff(array_keys($_POST), $expectedFields) !== []) {
            $this->renderLogin('نام کاربری یا رمز عبور صحیح نیست.', '', 422);
        }

        $usernameInput = $_POST['username'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!is_string($usernameInput) || !is_string($password)) {
            $this->renderLogin('نام کاربری یا رمز عبور صحیح نیست.', '', 422);
        }

        $username = strtolower(trim($usernameInput));
        $displayUsername = preg_match('/^[a-z0-9][a-z0-9._-]{2,63}$/', $username) ? $username : '';
        $credentialsHaveValidShape = $displayUsername !== ''
            && strlen($password) >= 1
            && strlen($password) <= 4096;
        $retryAfter = $this->consumeLoginLimits($displayUsername !== '' ? $displayUsername : 'invalid');

        if ($retryAfter !== null) {
            header('Retry-After: ' . $retryAfter);
            $this->renderLogin('تعداد تلاش‌های ورود زیاد است. لطفاً کمی بعد دوباره تلاش کنید.', $displayUsername, 429);
        }

        $admin = null;

        if ($credentialsHaveValidShape) {
            $connection = Connection::make($this->database);
            $authenticator = new Authenticator(
                new AdminRepository($connection),
                $this->app['key']
            );
            $admin = $authenticator->attempt($username, $password);
        }

        if ($admin === null) {
            $this->renderLogin('نام کاربری یا رمز عبور صحیح نیست.', $displayUsername, 422);
        }

        AuthSession::login($admin['id'], $admin['credential_version']);
        $this->redirect('/admin', 303);
    }

    public function logout(): never
    {
        $this->requireSecureTransport();
        $this->noStore();

        if ($this->currentAdminId() === null) {
            $this->redirect('/admin/login', 303);
        }

        if (!$this->isValidFormRequest()) {
            $this->renderLogin('درخواست خروج معتبر نیست.', '', 415);
        }

        $csrfToken = $_POST['csrf_token'] ?? null;
        $validFields = array_diff(array_keys($_POST), ['csrf_token']) === [];

        if (
            !$validFields
            || !SameOrigin::accepts($this->app['url'])
            || !is_string($csrfToken)
            || !Csrf::verify($csrfToken)
        ) {
            $this->renderLogin('اعتبار درخواست خروج تأیید نشد.', '', 403);
        }

        AuthSession::logout();
        $this->redirect('/admin/login', 303);
    }

    /** @param list<string> $allowedMethods */
    public function methodNotAllowed(array $allowedMethods): never
    {
        $this->requireSecureTransport();
        $this->noStore();
        http_response_code(405);
        header('Allow: ' . implode(', ', $allowedMethods));
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Method not allowed.';
        exit;
    }

    private function currentAdminId(): ?int
    {
        return AuthSession::adminId(
            $this->configuration['idle_timeout_seconds'],
            $this->configuration['absolute_timeout_seconds']
        );
    }

    private function consumeLoginLimits(string $username): ?int
    {
        $directory = $this->privatePath . '/storage/rate-limits';
        $remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $ipLimiter = new RateLimiter(
            $directory,
            $this->app['key'],
            $this->configuration['login_ip_attempts'],
            $this->configuration['login_window_seconds']
        );
        $usernameLimiter = new RateLimiter(
            $directory,
            $this->app['key'],
            $this->configuration['login_username_attempts'],
            $this->configuration['login_window_seconds']
        );
        $ipRetry = $ipLimiter->consume("admin-login-ip\0" . $remoteAddress);
        $usernameRetry = $usernameLimiter->consume("admin-login-username\0" . $username);

        if ($ipRetry === null && $usernameRetry === null) {
            return null;
        }

        return max($ipRetry ?? 0, $usernameRetry ?? 0);
    }

    private function isValidFormRequest(): bool
    {
        $contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
        $rawContentLength = $_SERVER['CONTENT_LENGTH'] ?? null;
        $contentLength = filter_var(
            $rawContentLength,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );

        return $contentType === 'application/x-www-form-urlencoded'
            && is_int($contentLength)
            && $contentLength <= $this->configuration['request_max_bytes'];
    }

    private function renderLogin(?string $errorMessage, string $username, int $status): never
    {
        http_response_code($status);
        $csrfToken = Csrf::token();
        require $this->privatePath . '/resources/views/admin/login.php';
        exit;
    }

    private function noStore(): void
    {
        header('Cache-Control: no-store, private, max-age=0');
        header('Pragma: no-cache');
        header('X-Robots-Tag: noindex, nofollow');
    }

    private function requireSecureTransport(): void
    {
        if ($this->app['environment'] !== 'production' || Session::isSecureRequest()) {
            return;
        }

        http_response_code(400);
        header('Cache-Control: no-store, private, max-age=0');
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'HTTPS is required.';
        exit;
    }

    private function redirect(string $path, int $status = 302): never
    {
        if (!in_array($path, ['/admin', '/admin/login'], true)) {
            throw new \LogicException('Unsafe redirect target.');
        }

        header('Location: ' . $path, true, $status);
        exit;
    }
}
