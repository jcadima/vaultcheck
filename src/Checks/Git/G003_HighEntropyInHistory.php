<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Git;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

/**
 * G003 — High-entropy token found in git history.
 *
 * Shannon entropy above 4.5 bits/char in a long token suggests
 * a random secret (API key, password, private key material) even
 * when the service format isn't recognised by the PatternRegistry.
 */
class G003_HighEntropyInHistory extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return $context->gitScanResult?->gitPresent ?? false;
    }

    public function run(ScanContext $context): FindingCollection
    {
        $matches = $context->gitScanResult->entropyMatches;
        if (empty($matches)) {
            return $this->empty();
        }

        $findings = [];
        foreach ($matches as $match) {
            $findings[] = $this->finding(
                checkId: 'G003',
                severity: Finding::SEVERITY_HIGH,
                message: sprintf(
                    'High-entropy token (entropy %.2f) found in commit %s (file: %s, value: %s).',
                    $match['entropy'],
                    substr($match['commit'], 0, 8),
                    $match['file'],
                    $match['redacted'],
                ),
                suggestion: 'Review whether this token is a secret. If so, rotate it and purge from history.',
            );
        }

        return $this->collection(...$findings);
    }
}
