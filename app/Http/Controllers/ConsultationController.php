<?php

declare(strict_types=1);

namespace Ara\Http\Controllers;

use Ara\Consultation\ConsultationRequestRepository;
use Ara\Consultation\ConsultationRequestValidator;
use Ara\Consultation\DuplicateSubmissionGuard;
use Ara\Database\Connection;
use Ara\Http\HttpException;
use Ara\Http\JsonRequest;
use Ara\Http\JsonResponse;
use Ara\Security\Csrf;
use Ara\Security\RateLimiter;
use Ara\Security\SameOrigin;

final class ConsultationController
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
        private readonly string $storagePath
    ) {
    }

    public function handle(): never
    {
        try {
            $this->handleRequest();
        } catch (HttpException $exception) {
            $payload = [
                'ok' => false,
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
            ];

            if ($exception->errors !== []) {
                $payload['errors'] = $exception->errors;
            }

            JsonResponse::send($exception->status, $payload, $exception->headers);
        }
    }

    private function handleRequest(): never
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            throw new HttpException(
                405,
                'method_not_allowed',
                'این مسیر فقط درخواست POST را می‌پذیرد.',
                [],
                ['Allow' => 'POST']
            );
        }

        $contentType = strtolower(trim(explode(';', (string) ($_SERVER['CONTENT_TYPE'] ?? ''))[0]));

        if ($contentType !== 'application/json') {
            throw new HttpException(415, 'unsupported_media_type', 'نوع محتوای درخواست پشتیبانی نمی‌شود.');
        }

        $contentLength = filter_var(
            $_SERVER['CONTENT_LENGTH'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0]]
        );

        if (
            $contentLength !== false
            && $contentLength !== null
            && $contentLength > $this->configuration['request_max_bytes']
        ) {
            throw new HttpException(413, 'payload_too_large', 'حجم درخواست بیشتر از حد مجاز است.');
        }

        $remoteAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $rateLimiter = new RateLimiter(
            $this->storagePath . '/rate-limits',
            $this->app['key'],
            $this->configuration['rate_limit'],
            $this->configuration['rate_window_seconds']
        );
        $retryAfter = $rateLimiter->consume($remoteAddress);

        if ($retryAfter !== null) {
            throw new HttpException(
                429,
                'rate_limited',
                'تعداد درخواست‌ها زیاد است. لطفاً کمی بعد دوباره تلاش کنید.',
                [],
                ['Retry-After' => (string) $retryAfter]
            );
        }

        if (!SameOrigin::accepts($this->app['url'])) {
            throw new HttpException(403, 'origin_rejected', 'اعتبار مبدأ درخواست تأیید نشد.');
        }

        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!Csrf::verify(is_string($csrfToken) ? $csrfToken : null)) {
            throw new HttpException(403, 'csrf_rejected', 'اعتبار نشست منقضی یا نامعتبر است. صفحه را تازه‌سازی کنید.');
        }

        $input = JsonRequest::read($this->configuration['request_max_bytes']);
        $validator = new ConsultationRequestValidator($this->configuration['services']);
        $request = $validator->validate($input);
        $repository = new ConsultationRequestRepository(Connection::make($this->database));
        $guard = new DuplicateSubmissionGuard(
            $this->storagePath . '/locks',
            $this->app['key']
        );

        $requestId = $guard->synchronized(
            $request,
            function () use ($repository, $request): ?int {
                if ($repository->existsRecently(
                    $request,
                    $this->configuration['duplicate_window_seconds']
                )) {
                    return null;
                }

                return $repository->create($request);
            }
        );

        if ($requestId === null) {
            throw new HttpException(
                409,
                'duplicate_submission',
                'درخواست مشابهی اخیراً ثبت شده است. لطفاً کمی بعد دوباره تلاش کنید.'
            );
        }

        JsonResponse::send(201, [
            'ok' => true,
            'message' => 'درخواست مشاوره با موفقیت ثبت شد.',
        ]);
    }
}
