<?php

declare(strict_types=1);

namespace Ara\Http\Controllers;

use Ara\Auth\AdminAccess;
use Ara\Consultation\AdminConsultationRepository;
use Ara\Security\Csrf;
use Ara\Security\SameOrigin;
use Ara\Security\Session;

final class AdminDashboardController
{
    private const STATUSES = ['new', 'in_progress', 'completed', 'archived'];
    private const PER_PAGE = 20;

    private readonly AdminAccess $access;

    /**
     * @param array<string, mixed> $app
     * @param array<string, mixed> $database
     * @param array<string, mixed> $auth
     */
    public function __construct(
        private readonly array $app,
        array $database,
        array $auth,
        private readonly string $privatePath
    ) {
        $this->access = new AdminAccess($app, $database, $auth);
    }

    public function dashboard(): never
    {
        $admin = $this->requireAdmin();
        $repository = $this->repository();
        $stats = $repository->statistics();
        $recentRequests = $repository->recent();
        $adminUsername = $admin['username'];
        $csrfToken = Csrf::token();
        $statusLabels = $this->statusLabels();
        $activeNavigation = 'dashboard';
        require $this->privatePath . '/resources/views/admin/home.php';
        exit;
    }

    public function consultations(): never
    {
        $admin = $this->requireAdmin();
        $search = $this->queryString('q', 100);
        $statusInput = $this->queryString('status', 20);
        $status = $statusInput === '' ? null : $statusInput;

        if ($status !== null && !in_array($status, self::STATUSES, true)) {
            $this->fail(422, 'فیلتر وضعیت معتبر نیست.');
        }

        $page = $this->queryPage();
        $result = $this->repository()->paginate($search, $status, $page, self::PER_PAGE);
        $pageCount = max(1, (int) ceil($result['total'] / self::PER_PAGE));

        if ($page > $pageCount) {
            $page = $pageCount;
            $result = $this->repository()->paginate($search, $status, $page, self::PER_PAGE);
        }

        $requests = $result['items'];
        $pagination = [
            'page' => $page,
            'pages' => $pageCount,
            'per_page' => self::PER_PAGE,
            'total' => $result['total'],
        ];
        $filters = ['q' => $search, 'status' => $status ?? ''];
        $notice = $this->queryNotice(['deleted']);
        $adminUsername = $admin['username'];
        $csrfToken = Csrf::token();
        $statusLabels = $this->statusLabels();
        $activeNavigation = 'consultations';
        require $this->privatePath . '/resources/views/admin/consultations.php';
        exit;
    }

    public function consultation(int $requestId): never
    {
        $admin = $this->requireAdmin();
        $request = $this->repository()->find($requestId);

        if ($request === null) {
            $this->fail(404, 'درخواست مشاوره پیدا نشد.');
        }

        $notice = $this->queryNotice(['status', 'notes']);
        $adminUsername = $admin['username'];
        $csrfToken = Csrf::token();
        $statusLabels = $this->statusLabels();
        $activeNavigation = 'consultations';
        require $this->privatePath . '/resources/views/admin/consultation.php';
        exit;
    }

    public function updateStatus(int $requestId): never
    {
        $admin = $this->requireAdmin();
        $this->validateMutation(['csrf_token', 'status']);
        $status = $_POST['status'] ?? null;

        if (!is_string($status) || !in_array($status, self::STATUSES, true)) {
            $this->fail(422, 'وضعیت انتخاب‌شده معتبر نیست.');
        }

        if (!$this->repository()->updateStatus($requestId, $status, $admin['id'])) {
            $this->fail(404, 'درخواست مشاوره پیدا نشد.');
        }

        $this->redirect('/admin/consultations/' . $requestId . '?updated=status');
    }

    public function updateNotes(int $requestId): never
    {
        $admin = $this->requireAdmin();
        $this->validateMutation(['admin_notes', 'csrf_token']);
        $notes = $_POST['admin_notes'] ?? null;

        if (!is_string($notes)) {
            $this->fail(422, 'یادداشت واردشده معتبر نیست.');
        }

        $notes = trim(str_replace(["\r\n", "\r"], "\n", $notes));

        if (
            preg_match('//u', $notes) !== 1
            || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $notes) === 1
            || $this->textLength($notes) > 5000
        ) {
            $this->fail(422, 'یادداشت باید حداکثر ۵۰۰۰ نویسه و بدون نویسه کنترلی باشد.');
        }

        if (!$this->repository()->updateNotes($requestId, $notes, $admin['id'])) {
            $this->fail(404, 'درخواست مشاوره پیدا نشد.');
        }

        $this->redirect('/admin/consultations/' . $requestId . '?updated=notes');
    }

    public function deleteConsultation(int $requestId): never
    {
        $this->requireAdmin();
        $this->validateMutation(['confirm_delete', 'csrf_token']);

        if (($_POST['confirm_delete'] ?? null) !== 'yes') {
            $this->fail(422, 'تأیید حذف الزامی است.');
        }

        if (!$this->repository()->delete($requestId)) {
            $this->fail(404, 'درخواست مشاوره پیدا نشد.');
        }

        $this->redirect('/admin/consultations?updated=deleted');
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

    public function notFound(): never
    {
        $this->requireAdmin();
        $this->fail(404, 'صفحه مدیریت موردنظر پیدا نشد.');
    }

    /** @return array{id: int, username: string} */
    private function requireAdmin(): array
    {
        $this->noStore();
        $this->requireSecureTransport();
        $admin = $this->access->currentAdmin();

        if ($admin === null) {
            $this->redirect('/admin/login');
        }

        return $admin;
    }

    private function repository(): AdminConsultationRepository
    {
        return new AdminConsultationRepository($this->access->connection());
    }

    /** @param list<string> $expectedFields */
    private function validateMutation(array $expectedFields): void
    {
        $contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
        $rawLength = $_SERVER['CONTENT_LENGTH'] ?? null;
        $contentLength = filter_var(
            $rawLength,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );

        if (
            $contentType !== 'application/x-www-form-urlencoded'
            || !is_int($contentLength)
            || $contentLength > 16384
        ) {
            $this->fail(415, 'ساختار درخواست معتبر نیست.');
        }

        $actualFields = array_keys($_POST);
        sort($actualFields);
        sort($expectedFields);

        if ($actualFields !== $expectedFields) {
            $this->fail(422, 'اطلاعات ارسال‌شده معتبر نیست.');
        }

        $csrfToken = $_POST['csrf_token'] ?? null;

        if (
            !SameOrigin::accepts($this->app['url'])
            || !is_string($csrfToken)
            || !Csrf::verify($csrfToken)
        ) {
            $this->fail(403, 'اعتبار درخواست تأیید نشد. صفحه را تازه‌سازی کنید.');
        }
    }

    private function queryString(string $key, int $maximumLength): string
    {
        $value = $_GET[$key] ?? '';

        if (!is_string($value)) {
            $this->fail(422, 'پارامتر جست‌وجو معتبر نیست.');
        }

        $value = trim($value);

        if (
            preg_match('//u', $value) !== 1
            || preg_match('/[\x00-\x1F\x7F]/', $value) === 1
            || $this->textLength($value) > $maximumLength
        ) {
            $this->fail(422, 'پارامتر جست‌وجو معتبر نیست.');
        }

        return $value;
    }

    private function queryPage(): int
    {
        $value = $_GET['page'] ?? '1';

        if (!is_string($value) && !is_int($value)) {
            $this->fail(422, 'شماره صفحه معتبر نیست.');
        }

        $page = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 1000000],
        ]);

        if ($page === false) {
            $this->fail(422, 'شماره صفحه معتبر نیست.');
        }

        return $page;
    }

    /** @param list<string> $allowed */
    private function queryNotice(array $allowed): ?string
    {
        $notice = $_GET['updated'] ?? null;

        return is_string($notice) && in_array($notice, $allowed, true) ? $notice : null;
    }

    private function textLength(string $value): int
    {
        $result = preg_match_all('/./us', $value, $matches);

        return $result === false ? PHP_INT_MAX : $result;
    }

    /** @return array<string, string> */
    private function statusLabels(): array
    {
        return [
            'new' => 'جدید',
            'in_progress' => 'در حال پیگیری',
            'completed' => 'تکمیل‌شده',
            'archived' => 'بایگانی‌شده',
        ];
    }

    private function requireSecureTransport(): void
    {
        if ($this->app['environment'] !== 'production' || Session::isSecureRequest()) {
            return;
        }

        $this->fail(400, 'برای ورود به مدیریت استفاده از HTTPS الزامی است.');
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
        echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<meta name="robots" content="noindex,nofollow"><title>خطای مدیریت</title>'
            . '<body style="font-family:system-ui,sans-serif;background:#0b0c0e;color:#f1f3f5;padding:2rem;line-height:2">'
            . '<main><h1>امکان انجام درخواست وجود ندارد</h1><p>' . e($message) . '</p>'
            . '<a style="color:#d7dbe0" href="/admin/consultations">بازگشت به درخواست‌ها</a></main></body></html>';
        exit;
    }

    private function redirect(string $location, int $status = 303): never
    {
        if (preg_match('#^/admin(?:/login|/consultations(?:/[1-9][0-9]*)?)?(?:\?updated=(?:status|notes|deleted))?$#D', $location) !== 1) {
            throw new \LogicException('Unsafe admin redirect target.');
        }

        header('Location: ' . $location, true, $status);
        exit;
    }
}
