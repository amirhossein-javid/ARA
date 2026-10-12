<?php

declare(strict_types=1);

namespace Ara\Http\Controllers;

use Ara\Auth\AdminAccess;
use Ara\Portfolio\PortfolioImageStorage;
use Ara\Portfolio\PortfolioRepository;
use Ara\Portfolio\PortfolioValidator;
use Ara\Security\Csrf;
use Ara\Security\SameOrigin;
use Ara\Security\Session;
use DomainException;
use Throwable;

final class AdminPortfolioController
{
    private const PER_PAGE = 15;

    private readonly AdminAccess $access;
    private readonly PortfolioImageStorage $images;

    /**
     * @param array<string, mixed> $app
     * @param array<string, mixed> $database
     * @param array<string, mixed> $auth
     * @param array<string, mixed> $portfolio
     */
    public function __construct(
        private readonly array $app,
        array $database,
        array $auth,
        private readonly array $portfolio,
        private readonly string $privatePath,
        string $publicPath
    ) {
        $this->access = new AdminAccess($app, $database, $auth);
        $this->images = new PortfolioImageStorage($publicPath, $portfolio);
    }

    public function index(): never
    {
        $admin = $this->requireAdmin();
        $status = $this->queryString('status', 12);
        $published = match ($status) {
            '' => null,
            'published' => true,
            'draft' => false,
            default => $this->fail(422, 'فیلتر انتشار معتبر نیست.'),
        };
        $page = $this->queryPage();
        $result = $this->repository()->paginate($published, $page, self::PER_PAGE);
        $pages = max(1, (int) ceil($result['total'] / self::PER_PAGE));

        if ($page > $pages) {
            $page = $pages;
            $result = $this->repository()->paginate($published, $page, self::PER_PAGE);
        }

        $projects = $result['items'];
        $pagination = ['page' => $page, 'pages' => $pages, 'total' => $result['total']];
        $filterStatus = $status;
        $notice = $this->queryNotice(['created', 'deleted', 'publication']);
        $this->render('portfolio.php', $admin, compact('projects', 'pagination', 'filterStatus', 'notice'));
    }

    public function createForm(?string $error = null, array $form = []): never
    {
        $admin = $this->requireAdmin();
        $project = array_merge($this->emptyProject(), $form);
        $isCreate = true;
        $this->render('portfolio-form.php', $admin, compact('project', 'isCreate', 'error'));
    }

    public function create(): never
    {
        $admin = $this->requireAdmin();
        $this->validateMultipart(['csrf_token', 'display_order', 'is_published', 'short_description', 'telegram_url', 'title']);
        $form = $this->formInput();
        $imagePath = null;

        try {
            $data = (new PortfolioValidator())->validate($form);
            $imagePath = $this->images->store($this->imageFile());
            $projectId = $this->repository()->create($data, $imagePath);
        } catch (DomainException $exception) {
            $project = array_merge($this->emptyProject(), $form);
            $isCreate = true;
            $error = $exception->getMessage();
            $this->render('portfolio-form.php', $admin, compact('project', 'isCreate', 'error'), 422);
        } catch (Throwable $throwable) {
            $this->images->delete($imagePath);
            throw $throwable;
        }

        $this->redirect('/admin/portfolio/' . $projectId . '?updated=created');
    }

    public function edit(int $projectId): never
    {
        $admin = $this->requireAdmin();
        $project = $this->repository()->find($projectId);

        if ($project === null) {
            $this->fail(404, 'پروژه پیدا نشد.');
        }

        $isCreate = false;
        $error = null;
        $notice = $this->queryNotice(['created', 'updated', 'image', 'publication']);
        $this->render('portfolio-form.php', $admin, compact('project', 'isCreate', 'error', 'notice'));
    }

    public function update(int $projectId): never
    {
        $admin = $this->requireAdmin();
        $this->validateMultipart(
            ['csrf_token', 'display_order', 'is_published', 'short_description', 'telegram_url', 'title'],
            ['remove_image']
        );
        $existing = $this->repository()->find($projectId);

        if ($existing === null) {
            $this->fail(404, 'پروژه پیدا نشد.');
        }

        $form = $this->formInput();

        if (isset($_POST['remove_image']) && $_POST['remove_image'] !== 'yes') {
            $this->fail(422, 'درخواست حذف تصویر معتبر نیست.');
        }

        $removeImage = ($_POST['remove_image'] ?? null) === 'yes';
        $newImagePath = null;

        try {
            $data = (new PortfolioValidator())->validate($form);
            $newImagePath = $this->images->store($this->imageFile());

            if ($newImagePath !== null && $removeImage) {
                throw new DomainException('برای جایگزینی تصویر، گزینه حذف تصویر فعلی را فعال نکنید.');
            }

            $finalImagePath = $newImagePath ?? ($removeImage ? null : $existing['image_path']);

            if (!$this->repository()->update($projectId, $data, $finalImagePath)) {
                throw new DomainException('پروژه پیدا نشد.');
            }
        } catch (DomainException $exception) {
            $this->images->delete($newImagePath);
            $project = array_merge($existing, $form);
            $isCreate = false;
            $error = $exception->getMessage();
            $notice = null;
            $this->render('portfolio-form.php', $admin, compact('project', 'isCreate', 'error', 'notice'), 422);
        } catch (Throwable $throwable) {
            $this->images->delete($newImagePath);
            throw $throwable;
        }

        if ($finalImagePath !== $existing['image_path']) {
            $this->images->delete(is_string($existing['image_path']) ? $existing['image_path'] : null);
        }

        $notice = $newImagePath !== null || $removeImage ? 'image' : 'updated';
        $this->redirect('/admin/portfolio/' . $projectId . '?updated=' . $notice);
    }

    public function setPublication(int $projectId): never
    {
        $this->requireAdmin();
        $this->validateUrlEncoded(['csrf_token', 'is_published']);
        $value = $_POST['is_published'] ?? null;

        if (!is_string($value) || !in_array($value, ['0', '1'], true)) {
            $this->fail(422, 'وضعیت انتشار معتبر نیست.');
        }

        if (!$this->repository()->setPublication($projectId, $value === '1')) {
            $this->fail(404, 'پروژه پیدا نشد.');
        }

        $this->redirect('/admin/portfolio/' . $projectId . '?updated=publication');
    }

    public function delete(int $projectId): never
    {
        $this->requireAdmin();
        $this->validateUrlEncoded(['confirm_delete', 'csrf_token']);

        if (($_POST['confirm_delete'] ?? null) !== 'yes') {
            $this->fail(422, 'تأیید حذف الزامی است.');
        }

        $project = $this->repository()->find($projectId);

        if ($project === null || !$this->repository()->delete($projectId)) {
            $this->fail(404, 'پروژه پیدا نشد.');
        }

        $this->images->delete(is_string($project['image_path']) ? $project['image_path'] : null);
        $this->redirect('/admin/portfolio?updated=deleted');
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

    private function repository(): PortfolioRepository
    {
        return new PortfolioRepository($this->access->connection());
    }

    /**
     * @param list<string> $requiredFields
     * @param list<string> $optionalFields
     */
    private function validateMultipart(array $requiredFields, array $optionalFields = []): void
    {
        $this->validateRequest('multipart/form-data', $this->portfolio['request_max_bytes']);
        $actual = array_keys($_POST);
        $allowed = array_merge($requiredFields, $optionalFields);

        if (array_diff($actual, $allowed) !== [] || array_diff($requiredFields, $actual) !== []) {
            $this->fail(422, 'فیلدهای فرم معتبر نیستند.');
        }

        if (array_diff(array_keys($_FILES), ['image']) !== []) {
            $this->fail(422, 'فایل ارسالی معتبر نیست.');
        }

        $this->validateCsrf();
    }

    /** @param list<string> $expectedFields */
    private function validateUrlEncoded(array $expectedFields): void
    {
        $this->validateRequest('application/x-www-form-urlencoded', 16384);
        $actual = array_keys($_POST);
        sort($actual);
        sort($expectedFields);

        if ($actual !== $expectedFields) {
            $this->fail(422, 'فیلدهای فرم معتبر نیستند.');
        }

        $this->validateCsrf();
    }

    private function validateRequest(string $expectedType, int $maximumBytes): void
    {
        $contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));
        $length = filter_var($_SERVER['CONTENT_LENGTH'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0],
        ]);

        if ($contentType !== $expectedType || !is_int($length) || $length > $maximumBytes) {
            $this->fail(415, 'ساختار یا اندازه درخواست معتبر نیست.');
        }
    }

    private function validateCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? null;

        if (
            !SameOrigin::accepts($this->app['url'])
            || !is_string($token)
            || !Csrf::verify($token)
        ) {
            $this->fail(403, 'اعتبار درخواست تأیید نشد. صفحه را تازه‌سازی کنید.');
        }
    }

    /** @return array<string, mixed>|null */
    private function imageFile(): ?array
    {
        $file = $_FILES['image'] ?? null;

        return is_array($file) ? $file : null;
    }

    /** @return array<string, mixed> */
    private function formInput(): array
    {
        return [
            'title' => $_POST['title'] ?? null,
            'short_description' => $_POST['short_description'] ?? null,
            'telegram_url' => $_POST['telegram_url'] ?? null,
            'display_order' => $_POST['display_order'] ?? null,
            'is_published' => $_POST['is_published'] ?? null,
        ];
    }

    /** @return array<string, mixed> */
    private function emptyProject(): array
    {
        return [
            'id' => null,
            'title' => '',
            'short_description' => '',
            'image_path' => null,
            'telegram_url' => '',
            'display_order' => 0,
            'is_published' => 0,
            'created_at' => null,
            'updated_at' => null,
        ];
    }

    private function queryString(string $key, int $maximumLength): string
    {
        $value = $_GET[$key] ?? '';

        if (!is_string($value) || strlen($value) > $maximumLength || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
            $this->fail(422, 'پارامتر فیلتر معتبر نیست.');
        }

        return trim($value);
    }

    private function queryPage(): int
    {
        $page = filter_var($_GET['page'] ?? '1', FILTER_VALIDATE_INT, [
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

    /** @param array<string, mixed> $variables */
    private function render(string $view, array $admin, array $variables, int $status = 200): never
    {
        http_response_code($status);
        extract($variables, EXTR_SKIP);
        $adminUsername = $admin['username'];
        $csrfToken = Csrf::token();
        $activeNavigation = 'portfolio';
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
        echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<meta name="robots" content="noindex,nofollow"><title>خطای مدیریت</title>'
            . '<body style="font-family:system-ui,sans-serif;background:#0b0c0e;color:#f1f3f5;padding:2rem;line-height:2">'
            . '<main><h1>امکان انجام درخواست وجود ندارد</h1><p>' . e($message) . '</p>'
            . '<a style="color:#d7dbe0" href="/admin/portfolio">بازگشت به نمونه‌کارها</a></main></body></html>';
        exit;
    }

    private function redirect(string $location): never
    {
        if (preg_match('#^/admin/(?:login|portfolio(?:/[1-9][0-9]*)?)?(?:\?updated=(?:created|updated|image|publication|deleted))?$#D', $location) !== 1) {
            throw new \LogicException('Unsafe portfolio redirect target.');
        }

        header('Location: ' . $location, true, 303);
        exit;
    }
}
