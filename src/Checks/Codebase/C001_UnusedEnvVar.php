<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Codebase;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class C001_UnusedEnvVar extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath()) && !empty($context->codebaseRefs);
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach (array_keys($context->envVars) as $key) {
            if (!isset($context->codebaseRefs[$key])) {
                $col->add($this->finding(
                    'C001',
                    Finding::SEVERITY_LOW,
                    "Environment variable '{$key}' is defined in .env but never referenced in the codebase.",
                    "Remove '{$key}' from .env if it is no longer needed, or verify it is used via a different mechanism.",
                    $context->envFilePath(),
                ));
            }
        }

        return $col;
    }
}
