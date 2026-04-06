<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class E015_BackupFiles extends BaseCheck
{
    private const BACKUP_PATTERNS = [
        '.env.bak',
        '.env.backup',
        '.env.old',
        '.env.orig',
        '.env.save',
        '.env.copy',
        '.env~',
        '.env.1',
        '.env.2',
    ];

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach (self::BACKUP_PATTERNS as $pattern) {
            $path = $context->projectPath . '/' . $pattern;
            if (is_file($path)) {
                $col->add($this->finding(
                    'E015',
                    Finding::SEVERITY_HIGH,
                    "Backup .env file found: '{$pattern}'. This file may contain real secrets and is likely untracked.",
                    "Delete '{$pattern}' immediately: rm {$path}",
                    $path,
                ));
            }
        }

        return $col;
    }
}
