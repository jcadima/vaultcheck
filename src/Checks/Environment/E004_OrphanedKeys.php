<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class E004_OrphanedKeys extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath()) && is_file($context->exampleFilePath());
    }

    public function run(ScanContext $context): FindingCollection
    {
        $envKeys     = array_keys($context->envVars);
        $exampleKeys = array_keys($context->exampleVars);

        // Keys in .env.example but not in .env (orphaned/stale example entries)
        $orphaned = array_diff($exampleKeys, $envKeys);

        $col = new FindingCollection();
        foreach ($orphaned as $key) {
            $col->add($this->finding(
                'E004',
                Finding::SEVERITY_LOW,
                "Key '{$key}' exists in .env.example but is absent from .env.",
                "Either add '{$key}' to .env or remove it from .env.example if it is no longer needed.",
                $context->exampleFilePath(),
            ));
        }

        return $col;
    }
}
