<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Strength;

/**
 * Shared helper for strength checks — identifies sensitive keys.
 */
trait StrengthHelper
{
    private const SENSITIVE_KEY_PATTERNS = [
        '/_KEY$/i',
        '/_SECRET$/i',
        '/_TOKEN$/i',
        '/_PASSWORD$/i',
        '/_PASS$/i',
        '/_PWD$/i',
        '/_AUTH$/i',
        '/_CREDENTIAL/i',
        '/_PRIVATE/i',
        '/^JWT_/i',
        '/^API_/i',
        '/^STRIPE_/i',
        '/^AWS_/i',
        '/^GITHUB_/i',
    ];

    protected function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEY_PATTERNS as $pattern) {
            if (preg_match($pattern, $key)) {
                return true;
            }
        }
        return false;
    }
}
