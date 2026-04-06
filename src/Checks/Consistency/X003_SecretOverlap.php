<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Consistency;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

/**
 * X003: Sensitive keys shared between production and non-production env files.
 */
class X003_SecretOverlap extends BaseCheck
{
    private const SENSITIVE_KEY_PATTERNS = [
        '/_KEY$/i',
        '/_SECRET$/i',
        '/_TOKEN$/i',
        '/_PASSWORD$/i',
        '/_PASS$/i',
        '/_PWD$/i',
    ];

    private const PRODUCTION_INDICATORS   = ['production', 'prod', 'live'];
    private const NON_PRODUCTION_INDICATORS = ['staging', 'stage', 'local', 'dev', 'development', 'test', 'testing'];

    public function isApplicable(ScanContext $context): bool
    {
        return count($context->multiEnvVars) >= 2;
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col  = new FindingCollection();
        $prod = [];
        $nonprod = [];

        foreach ($context->multiEnvVars as $filename => $vars) {
            $lower = strtolower($filename);
            if ($this->matchesAny($lower, self::PRODUCTION_INDICATORS)) {
                $prod[$filename] = $vars;
            } elseif ($this->matchesAny($lower, self::NON_PRODUCTION_INDICATORS)) {
                $nonprod[$filename] = $vars;
            }
        }

        foreach ($prod as $prodFile => $prodVars) {
            foreach ($nonprod as $npFile => $npVars) {
                foreach ($prodVars as $key => $value) {
                    if ($value === '' || !$this->isSensitiveKey($key)) {
                        continue;
                    }
                    if (isset($npVars[$key]) && $npVars[$key] === $value) {
                        $col->add($this->finding(
                            'X003',
                            Finding::SEVERITY_HIGH,
                            "Sensitive key '{$key}' has the same value in '{$prodFile}' (production) and '{$npFile}' (non-production).",
                            "Use separate credentials for production. Rotate the production '{$key}' immediately.",
                        ));
                    }
                }
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

    private function matchesAny(string $subject, array $terms): bool
    {
        foreach ($terms as $term) {
            if (str_contains($subject, $term)) {
                return true;
            }
        }
        return false;
    }
}
