<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Git;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

/**
 * G005 — Credential-looking values found in config/ directory history.
 *
 * Some teams hard-code secrets directly into config files (config/database.php,
 * config/services.php, etc.) rather than pulling them from env(). This check
 * finds pattern matches in commits that touched the config/ directory.
 */
class G005_SecretsInConfigDir extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return $context->gitScanResult?->gitPresent ?? false;
    }

    public function run(ScanContext $context): FindingCollection
    {
        $creds = $context->gitScanResult->configCredentials;
        if (empty($creds)) {
            return $this->empty();
        }

        $findings = [];
        foreach ($creds as $cred) {
            $keyLabel = $cred['key'] !== '' ? "key {$cred['key']}" : 'a credential';
            $findings[] = $this->finding(
                checkId: 'G005',
                severity: Finding::SEVERITY_HIGH,
                message: sprintf(
                    'Hard-coded %s found in config history (commit %s, file: %s, value: %s).',
                    $keyLabel,
                    substr($cred['commit'], 0, 8),
                    $cred['file'],
                    $cred['redacted'],
                ),
                suggestion: 'Move all secrets out of config/ files and into .env variables read via env(). '
                    . 'Purge the credential from git history and rotate it.',
            );
        }

        return $this->collection(...$findings);
    }
}
