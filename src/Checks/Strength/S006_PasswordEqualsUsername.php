<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Strength;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class S006_PasswordEqualsUsername extends BaseCheck
{
    private const PAIRS = [
        ['DB_USERNAME', 'DB_PASSWORD'],
        ['DB_USER',     'DB_PASS'],
        ['MAIL_USERNAME', 'MAIL_PASSWORD'],
        ['REDIS_USERNAME', 'REDIS_PASSWORD'],
    ];

    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath());
    }

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach (self::PAIRS as [$userKey, $passKey]) {
            $user = $context->envVars[$userKey] ?? '';
            $pass = $context->envVars[$passKey] ?? '';

            if ($user === '' || $pass === '') {
                continue;
            }

            if ($user === $pass) {
                $col->add($this->finding(
                    'S006',
                    Finding::SEVERITY_HIGH,
                    "Password '{$passKey}' is identical to username '{$userKey}'. This is an extremely weak credential.",
                    "Set a strong, unique password for '{$passKey}' that differs from the username.",
                    $context->envFilePath(),
                ));
            }
        }

        return $col;
    }
}
