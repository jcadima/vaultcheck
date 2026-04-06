<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Strength;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class S003_KnownWeakValues extends BaseCheck
{
    use StrengthHelper;

    private const WEAK_VALUES = [
        'password', 'password1', 'password123', 'passw0rd',
        'admin', 'admin123', 'administrator',
        'root', 'root123',
        'secret', 'secret123',
        'qwerty', 'qwerty123', 'qwertyuiop',
        'letmein', 'welcome', 'welcome1',
        'monkey', 'dragon', 'master',
        '123456', '1234567', '12345678', '123456789', '1234567890',
        'abc123', 'iloveyou', 'sunshine', 'princess',
        'test', 'test123', 'testing', 'testing123',
        'demo', 'demo123',
        'sample', 'example',
        'changeme', 'change_me', 'change-me',
        'replace_me', 'replace-me',
        'secret_key', 'my_secret', 'super_secret',
        'null', 'none', 'empty',
    ];

    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath());
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach ($context->envVars as $key => $value) {
            if ($value === '' || !$this->isSensitiveKey($key)) {
                continue;
            }

            if (in_array(strtolower($value), self::WEAK_VALUES, true)) {
                $col->add($this->finding(
                    'S003',
                    Finding::SEVERITY_HIGH,
                    "Secret '{$key}' uses a known-weak value. This is one of the most commonly guessed secrets.",
                    "Replace '{$key}' with a randomly generated, high-entropy value.",
                    $context->envFilePath(),
                ));
            }
        }

        return $col;
    }
}
