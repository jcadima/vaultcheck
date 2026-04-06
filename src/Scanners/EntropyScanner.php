<?php

declare(strict_types=1);

namespace VaultCheck\Scanners;

/**
 * Detects high-entropy tokens (potential secrets) in strings using Shannon entropy.
 *
 * Shannon entropy: H = -Σ(p_i * log2(p_i)) where p_i = count(char) / length
 * A random 32-byte Base64 string scores ~6 bits; typical words score ~3–4 bits.
 * Threshold of 4.5 catches most real secrets while avoiding English sentences.
 */
class EntropyScanner
{
    public const ENTROPY_THRESHOLD = 4.5;
    public const MIN_TOKEN_LENGTH  = 20;
    public const MAX_TOKEN_LENGTH  = 200;

    /**
     * Calculate Shannon entropy for a string.
     */
    public function calculate(string $value): float
    {
        $length = strlen($value);
        if ($length === 0) {
            return 0.0;
        }

        $freq = array_count_values(str_split($value));
        $entropy = 0.0;

        foreach ($freq as $count) {
            $p = $count / $length;
            $entropy -= $p * log($p, 2);
        }

        return $entropy;
    }

    /**
     * Extract candidate tokens from a line (split on = " ' spaces).
     * Returns tokens that are within acceptable length bounds.
     *
     * @return string[]
     */
    public function extractTokens(string $line): array
    {
        // Split on common delimiters in config lines
        $tokens = preg_split('/[\s=\'",;`]+/', $line) ?: [];
        $results = [];

        foreach ($tokens as $token) {
            $len = strlen($token);
            if ($len >= self::MIN_TOKEN_LENGTH && $len <= self::MAX_TOKEN_LENGTH) {
                $results[] = $token;
            }
        }

        return $results;
    }

    /**
     * Check whether a token looks like a known-safe non-secret value.
     * Returns true if we should SKIP this token (it's safe).
     */
    public function isKnownSafe(string $token): bool
    {
        // UUID format — common in configs, not secrets
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $token)) {
            return true;
        }

        // ISO 8601 timestamps
        if (preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}/', $token)) {
            return true;
        }

        // File paths
        if (str_starts_with($token, '/') || preg_match('#^[a-zA-Z]:\\\\#', $token)) {
            return true;
        }

        // URLs
        if (preg_match('#^https?://#', $token)) {
            return true;
        }

        // Pure hex hashes (git SHAs, MD5s, etc.) with no mixed case or symbols — high entropy but not secrets
        if (preg_match('/^[0-9a-f]{32,64}$/', $token)) {
            return true;
        }

        // Fully numeric
        if (ctype_digit($token)) {
            return true;
        }

        // PHP/Laravel cache strings that look like hashes
        if (preg_match('/^[a-zA-Z0-9_\-]{20,}$/', $token) && !preg_match('/[^a-zA-Z0-9_\-]/', $token)) {
            // Further filter: if it looks like a Laravel cache key or version string, skip
            if (preg_match('/^(v\d+\.|sha\d+\.|[a-z]+_[0-9]+_)/i', $token)) {
                return true;
            }
        }

        // npm/yarn package integrity hashes: sha512-<base64>, sha256-<base64>, sha1-<base64>
        if (preg_match('/^sha\d+-[A-Za-z0-9+\/=]+$/', $token)) {
            return true;
        }

        return false;
    }

    /**
     * Redact a value: show first 4 chars + **** + last 2 chars.
     */
    public function redact(string $value): string
    {
        $len = strlen($value);
        if ($len <= 6) {
            return str_repeat('*', $len);
        }
        return substr($value, 0, 4) . '****' . substr($value, -2);
    }
}
