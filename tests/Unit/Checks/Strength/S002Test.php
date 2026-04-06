<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Strength;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Strength\S002_OnlyLowercase;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class S002Test extends TestCase
{
    public function test_fires_when_sensitive_key_is_all_lowercase(): void
    {
        $context = $this->makeContext(['API_KEY' => 'alllowercasesecret']); // 18 chars, all lower
        $results = (new S002_OnlyLowercase())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('S002', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_LOW, $findings[0]->severity);
    }

    public function test_clean_when_value_has_mixed_case(): void
    {
        $context = $this->makeContext(['API_KEY' => 'MixedCaseSecret123!']);
        $results = (new S002_OnlyLowercase())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_clean_when_value_too_short(): void
    {
        $context = $this->makeContext(['API_KEY' => 'short']); // < 8 chars
        $results = (new S002_OnlyLowercase())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_ignores_non_sensitive_keys(): void
    {
        $context = $this->makeContext(['APP_NAME' => 'myappname']);
        $results = (new S002_OnlyLowercase())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    private function makeContext(array $envVars): ScanContext
    {
        return new ScanContext(
            projectPath:  '/tmp/fake',
            envVars:      $envVars,
            exampleVars:  [],
            envFiles:     [],
            multiEnvVars: [],
            isProduction: false,
        );
    }
}
