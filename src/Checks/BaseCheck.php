<?php

declare(strict_types=1);

namespace VaultCheck\Checks;

use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

abstract class BaseCheck
{
    abstract public function run(ScanContext $context): FindingCollection;

    public function isApplicable(ScanContext $context): bool
    {
        return true;
    }

    protected function finding(
        string  $checkId,
        string  $severity,
        string  $message,
        string  $suggestion = '',
        ?string $file = null,
        ?int    $line = null,
    ): Finding {
        return new Finding($checkId, $severity, $message, $suggestion, $file, $line);
    }

    protected function collection(Finding ...$findings): FindingCollection
    {
        $col = new FindingCollection();
        foreach ($findings as $f) {
            $col->add($f);
        }
        return $col;
    }

    protected function empty(): FindingCollection
    {
        return new FindingCollection();
    }
}
