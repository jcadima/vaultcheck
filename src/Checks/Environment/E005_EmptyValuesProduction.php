<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class E005_EmptyValuesProduction extends BaseCheck
{
    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath()) && $context->isProduction;
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach ($context->envVars as $key => $value) {
            if ($value === '') {
                $col->add($this->finding(
                    'E005',
                    Finding::SEVERITY_HIGH,
                    "Key '{$key}' has an empty value in a production environment.",
                    "Set a real value for '{$key}' or remove it if unused.",
                    $context->envFilePath(),
                ));
            }
        }

        return $col;
    }
}
