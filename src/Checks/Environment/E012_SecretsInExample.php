<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

/**
 * E012: Detects real-looking secret values in .env.example.
 * The example file should only contain placeholder/dummy values.
 */
class E012_SecretsInExample extends BaseCheck
{
    // Keys that commonly hold real secrets and whose values we inspect
    private const SENSITIVE_KEY_PATTERNS = [
        '/KEY$/i',
        '/SECRET$/i',
        '/TOKEN$/i',
        '/PASSWORD$/i',
        '/PASS$/i',
        '/PWD$/i',
        '/CREDENTIAL/i',
        '/PRIVATE/i',
        '/AUTH/i',
        '/API_/i',
    ];

    // Values that look real (high entropy proxy: long alphanum strings with mixed case/digits)
    private const MIN_SUSPICIOUS_LENGTH = 20;

    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->exampleFilePath());
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach ($context->exampleVars as $key => $value) {
            if ($value === '') {
                continue;
            }

            if (!$this->isSensitiveKey($key)) {
                continue;
            }

            if ($this->looksLikeRealSecret($value)) {
                $col->add($this->finding(
                    'E012',
                    Finding::SEVERITY_HIGH,
                    "Key '{$key}' in .env.example appears to contain a real secret value.",
                    "Replace the value of '{$key}' in .env.example with a placeholder like 'your-{$key}-here'.",
                    $context->exampleFilePath(),
                ));
            }
        }

        return $col;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEY_PATTERNS as $pattern) {
            if (preg_match($pattern, $key)) {
                return true;
            }
        }
        return false;
    }

    private function looksLikeRealSecret(string $value): bool
    {
        if (strlen($value) < self::MIN_SUSPICIOUS_LENGTH) {
            return false;
        }

        // Has mixed case AND digits — typical of generated secrets
        $hasMixedCase = preg_match('/[a-z]/', $value) && preg_match('/[A-Z]/', $value);
        $hasDigits    = preg_match('/\d/', $value);
        $hasSpecial   = preg_match('/[^a-zA-Z0-9]/', $value);

        return (bool) ($hasMixedCase && ($hasDigits || $hasSpecial));
    }
}
