<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Consistency;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class X001_SharedDbPassword extends BaseCheck
{
    private const KEY = 'DB_PASSWORD';

    public function isApplicable(ScanContext $context): bool
    {
        return count($context->multiEnvVars) >= 2;
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col    = new FindingCollection();
        $values = [];

        foreach ($context->multiEnvVars as $filename => $vars) {
            $value = $vars[self::KEY] ?? null;
            if ($value !== null && $value !== '') {
                $values[$filename] = $value;
            }
        }

        $fileNames = array_keys($values);
        for ($i = 0; $i < count($fileNames); $i++) {
            for ($j = $i + 1; $j < count($fileNames); $j++) {
                $fileA = $fileNames[$i];
                $fileB = $fileNames[$j];
                if ($values[$fileA] === $values[$fileB]) {
                    $col->add($this->finding(
                        'X001',
                        Finding::SEVERITY_HIGH,
                        self::KEY . " is identical in '{$fileA}' and '{$fileB}'. Environments should use separate credentials.",
                        "Generate a unique " . self::KEY . " for each environment.",
                    ));
                }
            }
        }

        return $col;
    }
}
