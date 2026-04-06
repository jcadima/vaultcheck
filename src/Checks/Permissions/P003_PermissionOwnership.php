<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Permissions;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class P003_PermissionOwnership extends BaseCheck
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

        // Group-writable bit (0020)
        if ($perms & 0x0010) {
            return $this->collection(
                $this->finding(
                    'P003',
                    Finding::SEVERITY_MEDIUM,
                    sprintf('.env is group-writable (permissions: %s). Other members of the file\'s group can modify it.', $this->formatPerms($perms)),
                    'Run: chmod 640 .env (or chmod 600 .env for stricter access)',
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
