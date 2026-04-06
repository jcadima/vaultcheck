<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Strength;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class S001_SecretTooShort extends BaseCheck
{
    use StrengthHelper;

    private const MIN_LENGTH = 16;

    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath());
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach ($context->envVars as $key => $value) {
            if ($value === '' || !$this->isSensitiveKey($key)) {
                continue;
            }

            // Strip base64: prefix for length check
            $check = str_starts_with($value, 'base64:') ? substr($value, 7) : $value;

            if (strlen($check) < self::MIN_LENGTH) {
                $col->add($this->finding(
                    'S001',
                    Finding::SEVERITY_MEDIUM,
                    "Secret '{$key}' is only " . strlen($check) . " characters — minimum recommended length is " . self::MIN_LENGTH . ".",
                    "Generate a longer, random value for '{$key}' (at least " . self::MIN_LENGTH . " characters).",
                    $context->envFilePath(),
                ));
            }
        }

        return $col;
    }
}
