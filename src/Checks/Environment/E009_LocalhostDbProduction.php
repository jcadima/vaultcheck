<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class E009_LocalhostDbProduction extends BaseCheck
{
    private const LOCAL_HOSTS = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];

    private const DB_HOST_KEYS = ['DB_HOST', 'DATABASE_HOST', 'REDIS_HOST', 'CACHE_HOST'];

    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath()) && $context->isProduction;
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach (self::DB_HOST_KEYS as $key) {
            $value = strtolower(trim($context->envVars[$key] ?? ''));
            if ($value !== '' && in_array($value, self::LOCAL_HOSTS, true)) {
                $col->add($this->finding(
                    'E009',
                    Finding::SEVERITY_HIGH,
                    "'{$key}' is set to a localhost address in production. This likely means you are pointing at a local/dev database.",
                    "Set '{$key}' to your production database host.",
                    $context->envFilePath(),
                ));
            }
        }

        return $col;
    }
}
