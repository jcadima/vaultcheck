<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Consistency;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

/**
 * X005: Keys present in .env.staging or .env.testing but absent from .env.example.
 */
class X005_UndocumentedStagingConfig extends BaseCheck
{
    private const STAGING_SUFFIXES = ['staging', 'stage', 'testing', 'test'];

    public function isApplicable(ScanContext $context): bool
    {
        if (!is_file($context->exampleFilePath())) {
            return false;
        }

        foreach (array_keys($context->multiEnvVars) as $filename) {
            foreach (self::STAGING_SUFFIXES as $suffix) {
                if (str_ends_with(strtolower($filename), $suffix)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col         = new FindingCollection();
        $exampleKeys = array_keys($context->exampleVars);

        foreach ($context->multiEnvVars as $filename => $vars) {
            if (!$this->isStagingFile($filename)) {
                continue;
            }

            foreach (array_keys($vars) as $key) {
                if (!in_array($key, $exampleKeys, true)) {
                    $col->add($this->finding(
                        'X005',
                        Finding::SEVERITY_LOW,
                        "Key '{$key}' exists in '{$filename}' but is not documented in .env.example.",
                        "Add '{$key}=' to .env.example so all team members know this key is required.",
                        $context->exampleFilePath(),
                    ));
                }
            }
        }

        return $col;
    }

    private function isStagingFile(string $filename): bool
    {
        $lower = strtolower($filename);
        foreach (self::STAGING_SUFFIXES as $suffix) {
            if (str_ends_with($lower, $suffix)) {
                return true;
            }
        }
        return false;
    }
}
