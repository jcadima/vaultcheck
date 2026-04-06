<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class E001_EnvFileMissing extends BaseCheck
{
    public function run(ScanContext $context): FindingCollection
    {
        if (!is_file($context->envFilePath())) {
            return $this->collection(
                $this->finding(
                    'E001',
                    Finding::SEVERITY_HIGH,
                    '.env file is missing from the project root.',
                    'Run: cp .env.example .env && fill in your values.',
                    $context->envFilePath(),
                )
            );
        }

        return $this->empty();
    }
}
