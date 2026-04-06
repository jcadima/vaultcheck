<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Git;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

/**
 * G004 — .env.bak or .env.backup was committed to git history.
 *
 * Backup files often contain older (but still valid) credentials and
 * are frequently forgotten. They are just as dangerous as a committed .env.
 */
class G004_EnvBackupCommitted extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return $context->gitScanResult?->gitPresent ?? false;
    }

    public function run(ScanContext $context): FindingCollection
    {
        if (!$context->gitScanResult->backupEverCommitted) {
            return $this->empty();
        }

        return $this->collection(
            $this->finding(
                checkId: 'G004',
                severity: Finding::SEVERITY_HIGH,
                message: '.env.bak or .env.backup was committed to git history.',
                suggestion: 'Remove the backup file and purge it from git history. '
                    . 'Rotate any credentials that were inside it.',
            ),
        );
    }
}
