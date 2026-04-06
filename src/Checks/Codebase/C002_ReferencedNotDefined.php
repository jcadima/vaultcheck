<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Codebase;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class C002_ReferencedNotDefined extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath()) && !empty($context->codebaseRefs);
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach ($context->codebaseRefs as $key => $usages) {
            if (!array_key_exists($key, $context->envVars)) {
                $firstUsage = $usages[0];
                $col->add($this->finding(
                    'C002',
                    Finding::SEVERITY_HIGH,
                    "Variable '{$key}' is referenced in code but not defined in .env (first seen: " . basename($firstUsage['file']) . ":{$firstUsage['line']}).",
                    "Add '{$key}=' to .env (and .env.example) with an appropriate value.",
                    $firstUsage['file'],
                    $firstUsage['line'],
                ));
            }
        }

        return $col;
    }
}
