<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class E008_AppDebugProduction extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath()) && $context->isProduction;
    }

    public function run(ScanContext $context): FindingCollection
    {
        $value = strtolower(trim($context->envVars['APP_DEBUG'] ?? ''));

        if (in_array($value, ['true', '1', 'yes', 'on'], true)) {
            return $this->collection(
                $this->finding(
                    'E008',
                    Finding::SEVERITY_CRITICAL,
                    'APP_DEBUG=true in a production environment exposes stack traces and config to end users.',
                    'Set APP_DEBUG=false in your production .env.',
                    $context->envFilePath(),
                )
            );
        }

        return $this->empty();
    }
}
