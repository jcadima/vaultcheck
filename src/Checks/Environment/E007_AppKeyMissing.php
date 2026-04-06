<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class E007_AppKeyMissing extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath());
    }

    public function run(ScanContext $context): FindingCollection
    {
        if (!array_key_exists('APP_KEY', $context->envVars)) {
            return $this->collection(
                $this->finding(
                    'E007',
                    Finding::SEVERITY_HIGH,
                    'APP_KEY is not set. Laravel cannot encrypt sessions or cookies without it.',
                    'Run: php artisan key:generate',
                    $context->envFilePath(),
                )
            );
        }

        $value = $context->envVars['APP_KEY'];

        if ($value === '') {
            return $this->collection(
                $this->finding(
                    'E007',
                    Finding::SEVERITY_HIGH,
                    'APP_KEY is empty.',
                    'Run: php artisan key:generate',
                    $context->envFilePath(),
                )
            );
        }

        if (!str_starts_with($value, 'base64:')) {
            return $this->collection(
                $this->finding(
                    'E007',
                    Finding::SEVERITY_MEDIUM,
                    "APP_KEY does not start with 'base64:' prefix — it may not be a valid Laravel key.",
                    "Run: php artisan key:generate to generate a properly formatted key.",
                    $context->envFilePath(),
                )
            );
        }

        $decoded = base64_decode(substr($value, 7), strict: true);
        if ($decoded === false || strlen($decoded) < 32) {
            return $this->collection(
                $this->finding(
                    'E007',
                    Finding::SEVERITY_HIGH,
                    'APP_KEY decoded value is too short (must be at least 32 bytes).',
                    'Run: php artisan key:generate',
                    $context->envFilePath(),
                )
            );
        }

        return $this->empty();
    }
}
