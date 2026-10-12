<?php

declare(strict_types=1);

namespace Ara\Seo;

use DomainException;
use RuntimeException;

final class SeoImageStorage
{
    private const TYPES = [
        IMAGETYPE_JPEG => ['mime' => 'image/jpeg', 'extension' => 'jpg'],
        IMAGETYPE_PNG => ['mime' => 'image/png', 'extension' => 'png'],
        IMAGETYPE_WEBP => ['mime' => 'image/webp', 'extension' => 'webp'],
    ];

    /** @param array<string, mixed> $configuration */
    public function __construct(private readonly string $publicPath, private readonly array $configuration)
    {
    }

    /** @param array<string, mixed>|null $file */
    public function store(?array $file): ?string
    {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        foreach (['error', 'size', 'tmp_name'] as $key) {
            if (!isset($file[$key]) || is_array($file[$key])) {
                throw new DomainException('فایل تصویر معتبر نیست.');
            }
        }
        if ((int) $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
            throw new DomainException('بارگذاری تصویر کامل یا معتبر نیست.');
        }

        $temporaryPath = (string) $file['tmp_name'];
        $actualSize = filesize($temporaryPath);
        if ($actualSize === false || $actualSize < 1 || $actualSize > $this->configuration['image_max_bytes']
            || (int) $file['size'] !== $actualSize) {
            throw new DomainException('حجم فایل تصویر معتبر نیست.');
        }

        $image = @getimagesize($temporaryPath);
        if (!is_array($image) || !isset($image[0], $image[1], $image[2], $image['mime'])) {
            throw new DomainException('محتوای فایل یک تصویر معتبر نیست.');
        }
        $type = self::TYPES[(int) $image[2]] ?? null;
        $width = (int) $image[0];
        $height = (int) $image[1];
        if ($type === null || $image['mime'] !== $type['mime']
            || $width < $this->configuration['image_min_width']
            || $height < $this->configuration['image_min_height']
            || $width > $this->configuration['image_max_width']
            || $height > $this->configuration['image_max_height']
            || ($width * $height) > $this->configuration['image_max_pixels']) {
            throw new DomainException('فرمت یا ابعاد تصویر مجاز نیست.');
        }
        if (!class_exists(\finfo::class)) {
            throw new RuntimeException('The fileinfo extension is required for secure image uploads.');
        }
        if ((new \finfo(FILEINFO_MIME_TYPE))->file($temporaryPath) !== $type['mime']) {
            throw new DomainException('نوع واقعی فایل تصویر مجاز نیست.');
        }

        $directory = $this->uploadDirectory();
        $filename = bin2hex(random_bytes(16)) . '.' . $type['extension'];
        $destination = $directory . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($temporaryPath, $destination)) {
            throw new RuntimeException('The uploaded SEO image could not be stored.');
        }
        @chmod($destination, 0644);
        return $this->configuration['upload_directory'] . '/' . $filename;
    }

    public function delete(?string $relativePath): bool
    {
        if ($relativePath === null || $relativePath === '' || $relativePath === SeoSettings::DEFAULTS['social_image_path']) {
            return true;
        }
        $quotedDirectory = preg_quote($this->configuration['upload_directory'], '#');
        if (preg_match('#^' . $quotedDirectory . '/[a-f0-9]{32}\.(?:jpg|png|webp)$#D', $relativePath) !== 1) {
            return false;
        }
        $directory = $this->uploadDirectory();
        $path = $directory . DIRECTORY_SEPARATOR . basename($relativePath);
        if (!is_file($path)) {
            return true;
        }
        $resolvedDirectory = realpath($directory);
        $resolvedPath = realpath($path);
        if ($resolvedDirectory === false || $resolvedPath === false
            || !str_starts_with($resolvedPath, $resolvedDirectory . DIRECTORY_SEPARATOR)) {
            return false;
        }
        return @unlink($resolvedPath);
    }

    private function uploadDirectory(): string
    {
        $publicRoot = realpath($this->publicPath);
        $relative = $this->configuration['upload_directory'];
        if ($publicRoot === false || !is_string($relative) || preg_match('#^[a-z0-9/_-]+$#D', $relative) !== 1) {
            throw new RuntimeException('The SEO upload directory configuration is invalid.');
        }
        $directory = $publicRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('The SEO upload directory could not be created.');
        }
        $resolved = realpath($directory);
        if ($resolved === false || !str_starts_with($resolved, $publicRoot . DIRECTORY_SEPARATOR) || !is_writable($resolved)) {
            throw new RuntimeException('The SEO upload directory is unavailable.');
        }
        return $resolved;
    }
}
