<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Environment;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Environment\E003_ExampleDrift;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class E003Test extends TestCase
{
    public function test_fires_when_env_key_missing_from_example(): void
    {
        $context = $this->makeContext(
            envVars:     ['APP_KEY' => 'abc', 'SECRET_KEY' => 'xyz'],
            exampleVars: ['APP_KEY' => ''],
        );
        $results = (new E003_ExampleDrift())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('E003', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_MEDIUM, $findings[0]->severity);
        $this->assertStringContainsString('SECRET_KEY', $findings[0]->message);
    }

    public function test_fires_once_per_missing_key(): void
    {
        $context = $this->makeContext(
            envVars:     ['A' => '1', 'B' => '2', 'C' => '3'],
            exampleVars: ['A' => ''],
        );
        $results = (new E003_ExampleDrift())->run($context);

        $this->assertSame(2, $results->count());
    }

    public function test_sensitive_key_missing_from_example_is_medium(): void
    {
        $context = $this->makeContext(
            envVars:     ['STRIPE_KEY' => 'sk_live_xxx'],
            exampleVars: [],
        );
        $findings = iterator_to_array((new E003_ExampleDrift())->run($context)->getIterator());

        $this->assertSame(1, count($findings));
        $this->assertSame(Finding::SEVERITY_MEDIUM, $findings[0]->severity);
    }

    public function test_non_sensitive_key_missing_from_example_is_low(): void
    {
        $context = $this->makeContext(
            envVars:     ['REDIS_DB' => '0'],
            exampleVars: [],
        );
        $findings = iterator_to_array((new E003_ExampleDrift())->run($context)->getIterator());

        $this->assertSame(1, count($findings));
        $this->assertSame(Finding::SEVERITY_LOW, $findings[0]->severity);
    }

    public function test_clean_when_all_env_keys_in_example(): void
    {
        $context = $this->makeContext(
            envVars:     ['APP_KEY' => 'abc'],
            exampleVars: ['APP_KEY' => ''],
        );
        $results = (new E003_ExampleDrift())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    private function makeContext(array $envVars = [], array $exampleVars = []): ScanContext
    {
        return new ScanContext(
            projectPath:  '/tmp/fake',
            envVars:      $envVars,
            exampleVars:  $exampleVars,
            envFiles:     [],
            multiEnvVars: [],
            isProduction: false,
        );
    }
}
