<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Strength;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class S002_OnlyLowercase extends BaseCheck
{
    use StrengthHelper;

    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath());
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach ($context->envVars as $key => $value) {
            if ($value === '' || strlen($value) < 8 || !$this->isSensitiveKey($key)) {
                continue;
            }

            // All lowercase, no digits, no special chars — very low entropy
            if (ctype_lower($value)) {
                $col->add($this->finding(
                    'S002',
                    Finding::SEVERITY_LOW,
                    "Secret '{$key}' contains only lowercase letters — this is a weak secret.",
                    "Use a mixed-case, alphanumeric+special-character value for '{$key}'.",
                    $context->envFilePath(),
                ));
            }
        }

        return $col;
    }
}
