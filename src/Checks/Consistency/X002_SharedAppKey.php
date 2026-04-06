<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Consistency;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class X002_SharedAppKey extends BaseCheck
{
    private const KEY = 'APP_KEY';

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
                        'X002',
                        Finding::SEVERITY_CRITICAL,
                        self::KEY . " is shared between '{$fileA}' and '{$fileB}'. A compromised key in one environment exposes all environments.",
                        "Generate a unique APP_KEY for each environment: php artisan key:generate",
                    ));
                }
            }
        }

        return $col;
    }
}
