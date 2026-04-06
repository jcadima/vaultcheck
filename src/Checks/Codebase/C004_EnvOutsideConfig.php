<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Codebase;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

/**
 * C004: env() called outside config/ directory.
 * In Laravel, env() should only be called from config/ files.
 * Direct env() calls in app code bypass config caching and break `php artisan config:cache`.
 */
class C004_EnvOutsideConfig extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return !empty($context->codebaseRefs);
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach ($context->codebaseRefs as $key => $usages) {
            foreach ($usages as $usage) {
                if (!$this->isConfigFile($usage['file'], $context->projectPath)) {
                    $col->add($this->finding(
                        'C004',
                        Finding::SEVERITY_MEDIUM,
                        "env('{$key}') is called outside of a config/ file (" . $this->relativePath($usage['file'], $context->projectPath) . ":{$usage['line']}).",
                        "Move env('{$key}') into a config/ file and reference it via config('...'). Direct env() calls break config caching.",
                        $usage['file'],
                        $usage['line'],
                    ));
                }
            }
        }

        return $col;
    }

    private function isConfigFile(string $filePath, string $projectPath): bool
    {
        $relative = $this->relativePath($filePath, $projectPath);
        return str_starts_with($relative, 'config/') || str_starts_with($relative, 'config\\');
    }

    private function relativePath(string $filePath, string $projectPath): string
    {
        $prefix = rtrim($projectPath, '/') . '/';
        return str_starts_with($filePath, $prefix)
            ? substr($filePath, strlen($prefix))
            : $filePath;
    }
}
