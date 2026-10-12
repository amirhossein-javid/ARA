<?php

declare(strict_types=1);

namespace Ara\Security;

final class SameOrigin
{
    public static function accepts(string $applicationUrl): bool
    {
        foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $header) {
            $value = trim((string) ($_SERVER[$header] ?? ''));

            if ($value !== '' && !self::matches($value, $applicationUrl)) {
                return false;
            }
        }

        return true;
    }

    private static function matches(string $candidate, string $expected): bool
    {
        $candidateParts = parse_url($candidate);
        $expectedParts = parse_url($expected);

        if (!is_array($candidateParts) || !is_array($expectedParts)) {
            return false;
        }

        $candidateScheme = strtolower((string) ($candidateParts['scheme'] ?? ''));
        $expectedScheme = strtolower((string) ($expectedParts['scheme'] ?? ''));
        $candidateHost = strtolower((string) ($candidateParts['host'] ?? ''));
        $expectedHost = strtolower((string) ($expectedParts['host'] ?? ''));

        if ($candidateScheme === '' || $candidateHost === '' || $expectedScheme === '' || $expectedHost === '') {
            return false;
        }

        return $candidateScheme === $expectedScheme
            && $candidateHost === $expectedHost
            && self::port($candidateParts, $candidateScheme) === self::port($expectedParts, $expectedScheme);
    }

    /** @param array<string, mixed> $parts */
    private static function port(array $parts, string $scheme): int
    {
        if (isset($parts['port'])) {
            return (int) $parts['port'];
        }

        return $scheme === 'https' ? 443 : 80;
    }
}
