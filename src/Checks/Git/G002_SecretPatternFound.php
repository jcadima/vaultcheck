<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Git;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

/**
 * G002 — Known secret pattern found in git history.
 *
 * Uses the PatternRegistry (Stripe, AWS, GitHub, etc.) to detect
 * service-specific credentials that were ever added in a commit.
 * One finding per distinct match.
 */
class G002_SecretPatternFound extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return $context->gitScanResult?->gitPresent ?? false;
    }

    public function run(ScanContext $context): FindingCollection
    {
        $matches = $context->gitScanResult->patternMatches;
        if (empty($matches)) {
            return $this->empty();
        }

        $findings = [];
        foreach ($matches as $match) {
            $findings[] = $this->finding(
                checkId: 'G002',
                severity: Finding::SEVERITY_CRITICAL,
                message: sprintf(
                    '%s credential found in commit %s (file: %s, value: %s).',
                    $match['pattern'],
                    substr($match['commit'], 0, 8),
                    $match['file'],
                    $match['redacted'],
                ),
                suggestion: sprintf(
                    'Rotate this %s credential immediately and purge the commit from git history.',
                    $match['service'],
                ),
            );
        }

        return $this->collection(...$findings);
    }
}
