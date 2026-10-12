<?php

declare(strict_types=1);

namespace Ara\Consultation;

use Ara\Http\HttpException;

final class ConsultationRequestValidator
{
    /** @param list<string> $services */
    public function __construct(private readonly array $services)
    {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{name: string, contact: string, service: string, message: string}
     */
    public function validate(array $input): array
    {
        $allowed = ['name', 'contact', 'service', 'message', 'website'];
        $unexpected = array_diff(array_keys($input), $allowed);

        if ($unexpected !== []) {
            throw new HttpException(422, 'validation_failed', 'اطلاعات ارسال‌شده معتبر نیست.');
        }

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $input) || !is_string($input[$field])) {
                throw new HttpException(422, 'validation_failed', 'اطلاعات ارسال‌شده معتبر نیست.');
            }

            if (!$this->isValidUtf8($input[$field]) || $this->containsControlCharacters($input[$field])) {
                throw new HttpException(422, 'validation_failed', 'اطلاعات ارسال‌شده معتبر نیست.');
            }
        }

        if (trim($input['website']) !== '') {
            throw new HttpException(422, 'validation_failed', 'ارسال درخواست امکان‌پذیر نیست.');
        }

        $name = $this->collapseWhitespace($input['name']);
        $contact = $this->collapseWhitespace($input['contact']);
        $service = trim($input['service']);
        $message = trim(str_replace(["\r\n", "\r"], "\n", $input['message']));
        $errors = [];

        if ($this->length($name) < 2 || $this->length($name) > 100) {
            $errors['name'] = 'نام باید بین ۲ تا ۱۰۰ نویسه باشد.';
        }

        if (!$this->validContact($contact)) {
            $errors['contact'] = 'یک ایمیل یا شماره تماس معتبر وارد کنید.';
        }

        if (!in_array($service, $this->services, true)) {
            $errors['service'] = 'موضوع همکاری انتخاب‌شده معتبر نیست.';
        }

        if ($this->length($message) < 10 || $this->length($message) > 2000) {
            $errors['message'] = 'توضیح پروژه باید بین ۱۰ تا ۲۰۰۰ نویسه باشد.';
        }

        if ($errors !== []) {
            throw new HttpException(
                422,
                'validation_failed',
                'لطفاً اطلاعات فرم را بررسی کنید.',
                $errors
            );
        }

        return [
            'name' => $name,
            'contact' => $contact,
            'service' => $service,
            'message' => $message,
        ];
    }

    private function validContact(string $contact): bool
    {
        $length = $this->length($contact);

        if ($length < 5 || $length > 190) {
            return false;
        }

        if (str_contains($contact, '@')) {
            return filter_var($contact, FILTER_VALIDATE_EMAIL) !== false;
        }

        $normalized = strtr($contact, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        if (!preg_match('/^\+?[0-9][0-9\s().-]{5,24}$/', $normalized)) {
            return false;
        }

        $digits = preg_replace('/\D/', '', $normalized);

        return is_string($digits) && strlen($digits) >= 7 && strlen($digits) <= 15;
    }

    private function collapseWhitespace(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim($value)) ?? '';
    }

    private function isValidUtf8(string $value): bool
    {
        return preg_match('//u', $value) === 1;
    }

    private function containsControlCharacters(string $value): bool
    {
        return preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1;
    }

    private function length(string $value): int
    {
        $result = preg_match_all('/./us', $value, $matches);

        return $result === false ? PHP_INT_MAX : $result;
    }
}
