<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Git;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

/**
 * G006 — .env is not listed in .gitignore.
 *
 * If .env is not ignored, any `git add .` or IDE commit helper will
 * silently include your secrets in the next commit.
 *
 * This check does NOT require git history — it only reads .gitignore.
 * It runs even when --skip-history is passed.
 */
class G006_EnvMissingFromGitignore extends BaseCheck
{
    /**
     * Only requires git to be present; doesn't need history scan.
     * Also runs when gitScanResult is null but .gitignore file exists.
     */
    public function isApplicable(ScanContext $context): bool
    {
        // Run if we have a git scan result, or if a .gitignore file exists
        return ($context->gitScanResult?->gitPresent ?? false)
            || file_exists($context->gitignorePath());
    }

    public function run(ScanContext $context): FindingCollection
    {
        $inGitignore = $context->gitScanResult?->envInGitignore
            ?? $this->checkGitignoreDirectly($context->gitignorePath());

        if ($inGitignore) {
            return $this->empty();
        }

        return $this->collection(
            $this->finding(
                checkId: 'G006',
                severity: Finding::SEVERITY_CRITICAL,
                message: '.env is not listed in .gitignore.',
                suggestion: 'Add ".env" to your .gitignore file immediately to prevent accidental commits of secrets.',
            ),
        );
    }

    private function checkGitignoreDirectly(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#')) {
                continue;
            }
            if ($line === '.env' || $line === '/.env' || $line === '.env*') {
                return true;
            }
        }
        return false;
    }
}
