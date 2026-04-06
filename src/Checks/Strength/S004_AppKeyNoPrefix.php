<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Strength;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

/**
 * S004: APP_KEY is set and non-empty but lacks the base64: prefix.
 * From a strength perspective, a raw (non-base64) key is likely shorter
 * and weaker than a properly generated Laravel key.
 */
class S004_AppKeyNoPrefix extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        if (!is_file($context->envFilePath())) {
            return false;
        }
        $value = $context->envVars['APP_KEY'] ?? '';
        return $value !== '' && !str_starts_with($value, 'base64:');
    }

    public function run(ScanContext $context): FindingCollection
    {
        $value = $context->envVars['APP_KEY'];

        return $this->collection(
            $this->finding(
                'S004',
                Finding::SEVERITY_MEDIUM,
                "APP_KEY is set but missing the 'base64:' prefix. Laravel-generated keys are base64-encoded 32-byte random values — a raw key is likely weaker.",
                "Regenerate with: php artisan key:generate",
                $context->envFilePath(),
            )
        );
    }
}
