<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Permissions;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class P002_WorldWritable extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath());
    }

    public function run(ScanContext $context): FindingCollection
    {
        $path  = $context->envFilePath();
        $perms = fileperms($path);

        if ($perms === false) {
            return $this->empty();
        }

        if ($perms & 0x0002) {
            return $this->collection(
                $this->finding(
                    'P002',
                    Finding::SEVERITY_CRITICAL,
                    sprintf('.env is world-writable (permissions: %s). Any user on the system can overwrite your secrets.', $this->formatPerms($perms)),
                    'Run: chmod 600 .env',
                    $path,
                )
            );
        }

        return $this->empty();
    }

    private function formatPerms(int $perms): string
    {
        return substr(sprintf('%o', $perms), -4);
    }
}
