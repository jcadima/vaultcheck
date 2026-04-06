<?php

declare(strict_types=1);

namespace VaultCheck\Patterns;

/**
 * Loads and applies all known secret patterns against text.
 */
class PatternRegistry
{
    /** @var SecretPattern[] */
    private array $patterns;

    public function __construct()
    {
        $this->patterns = require dirname(__DIR__, 2) . '/patterns/registry.php';
    }

    /**
     * @return SecretPattern[]
     */
    public function getPatterns(): array
    {
        return $this->patterns;
    }

    /**
     * Scan a block of text for known secret patterns.
     *
     * @return array<int, array{pattern: string, service: string, redacted: string, commit: string, file: string}>
     */
    public function scan(string $text, string $commitHash, string $filePath): array
    {
        $results = [];

        foreach ($this->patterns as $pattern) {
            if (preg_match_all($pattern->regex, $text, $matches)) {
                foreach ($matches[0] as $match) {
                    $results[] = [
                        'pattern' => $pattern->name,
                        'service' => $pattern->service,
                        'redacted' => $this->redact($match),
                        'commit'  => $commitHash,
                        'file'    => $filePath,
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Look up the rotation URL for a given service name.
     */
    public function rotationUrl(string $service): string
    {
        foreach ($this->patterns as $pattern) {
            if ($pattern->service === $service && $pattern->rotationUrl !== '') {
                return $pattern->rotationUrl;
            }
        }
        return '';
    }

    private function redact(string $value): string
    {
        $len = strlen($value);
        if ($len <= 6) {
            return str_repeat('*', $len);
        }
        return substr($value, 0, 4) . '****' . substr($value, -2);
    }
}
