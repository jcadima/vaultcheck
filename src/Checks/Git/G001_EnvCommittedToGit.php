<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Git;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

/**
 * G001 — .env file was committed to git history.
 *
 * Even if .env is now in .gitignore, the values inside it were
 * exposed the moment they entered the repository history. Anyone
 * who ever cloned the repo could still access them.
 */
class G001_EnvCommittedToGit extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return $context->gitScanResult?->gitPresent ?? false;
    }

    public function run(ScanContext $context): FindingCollection
    {
        if (!$context->gitScanResult->envEverCommitted) {
            return $this->empty();
        }

        return $this->collection(
            $this->finding(
                checkId: 'G001',
                severity: Finding::SEVERITY_CRITICAL,
                message: '.env was committed to git history.',
                suggestion: 'Run `git filter-repo` (or BFG Repo-Cleaner) to purge the file from history, '
                    . 'then rotate all credentials that were exposed.',
            ),
        );
    }
}
