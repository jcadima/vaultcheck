<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class E006_PlaceholderValues extends BaseCheck
{
    private const PLACEHOLDERS = [
        'your-secret-key',
        'your_secret_key',
        'change_me',
        'changeme',
        'change-me',
        'replace_me',
        'replace-me',
        'todo',
        'fixme',
        'placeholder',
        'example',
        'xxx',
        'yyy',
        'zzz',
        'secret',
        'password',
        '1234',
        '12345',
        '123456',
        'test',
        'dummy',
        'fake',
        'null',
        'none',
        'n/a',
    ];

    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath());
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach ($context->envVars as $key => $value) {
            if ($this->isPlaceholder($value)) {
                $col->add($this->finding(
                    'E006',
                    Finding::SEVERITY_MEDIUM,
                    "Key '{$key}' appears to have a placeholder value ('{$value}').",
                    "Replace the placeholder in '{$key}' with a real, secure value.",
                    $context->envFilePath(),
                ));
            }
        }

        return $col;
    }

    private function isPlaceholder(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        $lower = strtolower(trim($value));
        return in_array($lower, self::PLACEHOLDERS, true);
    }
}
