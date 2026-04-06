<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class E014_FileDrivers extends BaseCheck
{
    private const FILE_DRIVER_KEYS = [
        'CACHE_DRIVER'   => ['file', 'array'],
        'SESSION_DRIVER' => ['file', 'array', 'cookie'],
        'QUEUE_DRIVER'   => ['sync'],
        'QUEUE_CONNECTION' => ['sync'],
        'MAIL_MAILER'    => ['log', 'array'],
    ];

    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath()) && $context->isProduction;
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach (self::FILE_DRIVER_KEYS as $key => $developmentDrivers) {
            $value = strtolower(trim($context->envVars[$key] ?? ''));

            if ($value === '') {
                continue;
            }

            if (in_array($value, $developmentDrivers, true)) {
                $col->add($this->finding(
                    'E014',
                    Finding::SEVERITY_MEDIUM,
                    "'{$key}={$value}' is a development driver and should not be used in production.",
                    "Change '{$key}' to a production-appropriate driver (e.g. redis, database).",
                    $context->envFilePath(),
                ));
            }
        }

        return $col;
    }
}
