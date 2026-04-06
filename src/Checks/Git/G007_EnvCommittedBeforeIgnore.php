<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Git;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

/**
 * G007 — .env was committed before .gitignore was first added.
 *
 * Even if .env is in .gitignore today, if it was committed before
 * the ignore rule was set up, the secrets were exposed. This is a
 * "closed the barn door after the horse escaped" situation.
 */
class G007_EnvCommittedBeforeIgnore extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return $context->gitScanResult?->gitPresent ?? false;
    }

    public function run(ScanContext $context): FindingCollection
    {
        if (!$context->gitScanResult->envCommittedBeforeIgnore) {
            return $this->empty();
        }

        return $this->collection(
            $this->finding(
                checkId: 'G007',
                severity: Finding::SEVERITY_HIGH,
                message: '.env was committed to git before .gitignore was set up.',
                suggestion: 'Even though .env is now ignored, the earlier commit still contains your secrets. '
                    . 'Purge the commit from history and rotate all credentials that were exposed.',
            ),
        );
    }
}
