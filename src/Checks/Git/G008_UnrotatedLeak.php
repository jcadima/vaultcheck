<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Git;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

/**
 * G008 — Current .env value was found in git history (unrotated leak).
 *
 * This is the most actionable finding in the entire tool. It means:
 * - A secret WAS committed to the repository at some point
 * - The SAME secret is STILL in use today
 *
 * This is a CRITICAL finding because the credential has been compromised
 * AND has not been rotated. One finding per affected key.
 */
class G008_UnrotatedLeak extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return $context->gitScanResult?->gitPresent ?? false;
    }

    public function run(ScanContext $context): FindingCollection
    {
        $leaked = $context->gitScanResult->currentSecretsInHistory;
        if (empty($leaked)) {
            return $this->empty();
        }

        $findings = [];
        foreach (array_keys($leaked) as $key) {
            $findings[] = $this->finding(
                checkId: 'G008',
                severity: Finding::SEVERITY_CRITICAL,
                message: sprintf(
                    '%s: current value was found in git history — this secret was leaked and has NOT been rotated.',
                    $key,
                ),
                suggestion: sprintf(
                    'Rotate %s immediately. Generate a new value, update .env, and revoke the old one '
                    . 'in the relevant service dashboard. Then purge it from git history.',
                    $key,
                ),
            );
        }

        return $this->collection(...$findings);
    }
}
