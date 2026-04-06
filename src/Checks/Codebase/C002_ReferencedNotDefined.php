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
                $allHaveDefaults = array_reduce(
                    $usages,
                    fn(bool $carry, array $u) => $carry && $u['hasDefault'],
                    true
                );
                $configOnly = $this->isConfigOnly($usages);

                // Framework config files (config/*.php) legitimately reference optional env vars
                // for integrations most projects don't use. Treat all config-only refs as LOW.
                $severity = match(true) {
                    $configOnly                      => Finding::SEVERITY_LOW,
                    !$configOnly && $allHaveDefaults => Finding::SEVERITY_MEDIUM,
                    default                          => Finding::SEVERITY_HIGH,
                };

                $firstUsage = $usages[0];
                $col->add($this->finding(
                    'C002',
                    $severity,
                    "Variable '{$key}' is referenced in code but not defined in .env (first seen: " . basename($firstUsage['file']) . ":{$firstUsage['line']}).",
                    "Add '{$key}=' to .env (and .env.example) with an appropriate value.",
                    $firstUsage['file'],
                    $firstUsage['line'],
                ));
            }
        }

        return $col;
    }

    private function isConfigOnly(array $usages): bool
    {
        foreach ($usages as $u) {
            if (!str_starts_with($u['file'], 'config/')) {
                return false;
            }
        }
        return true;
    }
}
