<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Checks\Strength\StrengthHelper;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class E003_ExampleDrift extends BaseCheck
{
    use StrengthHelper;
    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath()) && is_file($context->exampleFilePath());
    }

    public function run(ScanContext $context): FindingCollection
    {
        $envKeys     = array_keys($context->envVars);
        $exampleKeys = array_keys($context->exampleVars);

        $missingFromExample = array_diff($envKeys, $exampleKeys);

        $col = new FindingCollection();
        foreach ($missingFromExample as $key) {
            // Credential/secret keys missing from .env.example are MEDIUM — developers cloning
            // the project need to know these integrations exist. Config-level keys are LOW.
            $severity = $this->isSensitiveKey($key)
                ? Finding::SEVERITY_MEDIUM
                : Finding::SEVERITY_LOW;

            $col->add($this->finding(
                'E003',
                $severity,
                "Key '{$key}' is defined in .env but missing from .env.example.",
                "Add '{$key}=' (with a placeholder value) to .env.example.",
                $context->exampleFilePath(),
            ));
        }

        return $col;
    }
}
