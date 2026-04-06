<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Environment;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Environment\E012_SecretsInExample;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class E012Test extends TestCase
{
    public function test_fires_when_real_secret_in_example(): void
    {
        // A realistic-looking API key: long, mixed case, digits
        $context = $this->makeContext(['API_KEY' => 'sk_live_AbCdEf1234567890XyZ']);
        $results = (new E012_SecretsInExample())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('E012', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
    }

    public function test_clean_when_placeholder_value(): void
    {
        $context = $this->makeContext(['API_KEY' => 'your-api-key-here']);
        $results = (new E012_SecretsInExample())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_clean_when_empty_value(): void
    {
        $context = $this->makeContext(['API_KEY' => '']);
        $results = (new E012_SecretsInExample())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_clean_for_non_sensitive_keys(): void
    {
        // APP_NAME is not a sensitive key
        $context = $this->makeContext(['APP_NAME' => 'MyLongAppNameWith123AndMixedCase']);
        $results = (new E012_SecretsInExample())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    private function makeContext(array $exampleVars): ScanContext
    {
        return new ScanContext(
            projectPath:  '/tmp/fake',
            envVars:      [],
            exampleVars:  $exampleVars,
            envFiles:     [],
            multiEnvVars: [],
            isProduction: false,
        );
    }
}
