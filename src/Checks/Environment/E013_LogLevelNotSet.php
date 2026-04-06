<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class E013_LogLevelNotSet extends BaseCheck
{
    private const LOG_KEYS = ['LOG_LEVEL', 'APP_LOG_LEVEL', 'LOG_CHANNEL'];

    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath());
    }

    public function run(ScanContext $context): FindingCollection
    {
        foreach (self::LOG_KEYS as $key) {
            if (array_key_exists($key, $context->envVars) && $context->envVars[$key] !== '') {
                return $this->empty();
            }
        }

        return $this->collection(
            $this->finding(
                'E013',
                Finding::SEVERITY_LOW,
                'No log level is configured (LOG_LEVEL / LOG_CHANNEL not found in .env).',
                'Add LOG_LEVEL=error (production) or LOG_LEVEL=debug (development) to .env.',
                $context->envFilePath(),
            )
        );
    }
}
