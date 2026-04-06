<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Codebase;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class C003_NoDefaultValue extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return !empty($context->codebaseRefs);
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();
        $reported = [];

        foreach ($context->codebaseRefs as $key => $usages) {
            foreach ($usages as $usage) {
                if (!$usage['hasDefault'] && !isset($reported[$key])) {
                    $reported[$key] = true;
                    $col->add($this->finding(
                        'C003',
                        Finding::SEVERITY_MEDIUM,
                        "env('{$key}') is called without a default value — the app will receive null if the variable is missing.",
                        "Add a safe default: env('{$key}', 'fallback-value') or ensure '{$key}' is always set.",
                        $usage['file'],
                        $usage['line'],
                    ));
                }
            }
        }

        return $col;
    }
}
