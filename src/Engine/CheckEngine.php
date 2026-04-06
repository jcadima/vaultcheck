<?php

declare(strict_types=1);

namespace VaultCheck\Engine;

use VaultCheck\Checks\BaseCheck;

class CheckEngine
{
    /** @var BaseCheck[] */
    private array $checks = [];

    public function register(BaseCheck $check): void
    {
        $this->checks[] = $check;
    }

    public function registerMany(array $checks): void
    {
        foreach ($checks as $check) {
            $this->register($check);
        }
    }

    public function run(ScanContext $context): FindingCollection
    {
        $collection = new FindingCollection();

        foreach ($this->checks as $check) {
            if (!$check->isApplicable($context)) {
                continue;
            }
            $findings = $check->run($context);
            $collection->merge($findings);
        }

        return $collection;
    }
}
