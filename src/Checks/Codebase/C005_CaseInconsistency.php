<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Codebase;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

/**
 * C005: Env variable referenced with different casing in code vs .env definition.
 * e.g. .env has DATABASE_URL but code calls env('database_url')
 */
class C005_CaseInconsistency extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath()) && !empty($context->codebaseRefs);
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        // Build a map of uppercase -> canonical key from .env
        $canonicalKeys = [];
        foreach (array_keys($context->envVars) as $key) {
            $canonicalKeys[strtoupper($key)] = $key;
        }

        foreach ($context->codebaseRefs as $refKey => $usages) {
            $upper = strtoupper($refKey);
            if (isset($canonicalKeys[$upper]) && $canonicalKeys[$upper] !== $refKey) {
                $canonical = $canonicalKeys[$upper];
                $first = $usages[0];
                $col->add($this->finding(
                    'C005',
                    Finding::SEVERITY_LOW,
                    "env('{$refKey}') in code doesn't match .env definition '{$canonical}' (case mismatch).",
                    "Use consistent casing — env('{$canonical}') to match the .env definition.",
                    $first['file'],
                    $first['line'],
                ));
            }
        }

        return $col;
    }
}
