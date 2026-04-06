<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class E002_ExampleMissing extends BaseCheck
{
    public function run(ScanContext $context): FindingCollection
    {
        if (!is_file($context->exampleFilePath())) {
            return $this->collection(
                $this->finding(
                    'E002',
                    Finding::SEVERITY_MEDIUM,
                    '.env.example file is missing.',
                    'Create .env.example with all required keys (no real values) and commit it.',
                    $context->exampleFilePath(),
                )
            );
        }

        return $this->empty();
    }
}
