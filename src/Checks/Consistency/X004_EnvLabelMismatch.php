<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Consistency;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

/**
 * X004: APP_ENV value inside the file doesn't match what the filename suggests.
 * e.g. .env.staging contains APP_ENV=production
 */
class X004_EnvLabelMismatch extends BaseCheck
{
    private const FILE_TO_EXPECTED_ENV = [
        'staging'     => ['staging', 'stage'],
        'stage'       => ['staging', 'stage'],
        'production'  => ['production', 'prod', 'live'],
        'prod'        => ['production', 'prod', 'live'],
        'local'       => ['local', 'development', 'dev'],
        'dev'         => ['local', 'development', 'dev'],
        'development' => ['local', 'development', 'dev'],
        'testing'     => ['testing', 'test'],
        'test'        => ['testing', 'test'],
    ];

    public function isApplicable(ScanContext $context): bool
    {
        return count($context->multiEnvVars) >= 2;
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach ($context->multiEnvVars as $filename => $vars) {
            // Only process named .env.* files, not the root .env
            if ($filename === '.env') {
                continue;
            }

            $appEnv = strtolower(trim($vars['APP_ENV'] ?? ''));
            if ($appEnv === '') {
                continue;
            }

            // Extract the environment label from filename (e.g. .env.staging -> staging)
            $label = strtolower(ltrim(str_replace('.env', '', $filename), '.'));

            if (isset(self::FILE_TO_EXPECTED_ENV[$label])) {
                $expected = self::FILE_TO_EXPECTED_ENV[$label];
                if (!in_array($appEnv, $expected, true)) {
                    $col->add($this->finding(
                        'X004',
                        Finding::SEVERITY_MEDIUM,
                        "File '{$filename}' contains APP_ENV={$appEnv}, but the filename suggests it should be one of: " . implode(', ', $expected) . ".",
                        "Set APP_ENV to match the file's intended environment, or rename the file.",
                        $context->projectPath . '/' . $filename,
                    ));
                }
            }
        }

        return $col;
    }
}
