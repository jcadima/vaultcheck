<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Strength;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class S005_JwtSecretTooShort extends BaseCheck
{
    private const JWT_KEYS   = ['JWT_SECRET', 'JWT_KEY', 'JWT_SIGNING_KEY', 'JWT_PRIVATE_KEY'];
    private const MIN_LENGTH = 32;

    public function isApplicable(ScanContext $context): bool
    {
        if (!is_file($context->envFilePath())) {
            return false;
        }
        foreach (self::JWT_KEYS as $key) {
            if (isset($context->envVars[$key]) && $context->envVars[$key] !== '') {
                return true;
            }
        }
        return false;
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach (self::JWT_KEYS as $key) {
            $value = $context->envVars[$key] ?? '';
            if ($value === '') {
                continue;
            }

            if (strlen($value) < self::MIN_LENGTH) {
                $col->add($this->finding(
                    'S005',
                    Finding::SEVERITY_HIGH,
                    "JWT secret '{$key}' is only " . strlen($value) . " characters. JWT secrets should be at least " . self::MIN_LENGTH . " characters to resist brute-force attacks.",
                    "Use a randomly generated string of at least " . self::MIN_LENGTH . " characters for '{$key}'.",
                    $context->envFilePath(),
                ));
            }
        }

        return $col;
    }
}
