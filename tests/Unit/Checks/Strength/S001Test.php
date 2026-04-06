<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Strength;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Strength\S001_SecretTooShort;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class S001Test extends TestCase
{
    public function test_fires_when_sensitive_key_value_too_short(): void
    {
        $context = $this->makeContext(['DB_PASSWORD' => 'short']); // 5 chars < 16
        $results = (new S001_SecretTooShort())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('S001', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_MEDIUM, $findings[0]->severity);
    }

    public function test_clean_when_secret_is_long_enough(): void
    {
        $context = $this->makeContext(['DB_PASSWORD' => 'this-is-a-strong-password123!']);
        $results = (new S001_SecretTooShort())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_ignores_non_sensitive_keys(): void
    {
        $context = $this->makeContext(['APP_NAME' => 'short']); // not sensitive
        $results = (new S001_SecretTooShort())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_ignores_empty_values(): void
    {
        $context = $this->makeContext(['DB_PASSWORD' => '']);
        $results = (new S001_SecretTooShort())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_strips_base64_prefix_before_length_check(): void
    {
        // base64: + 5 chars = 12 total, but real length is 5 < 16
        $context = $this->makeContext(['APP_KEY' => 'base64:short']);
        $results = (new S001_SecretTooShort())->run($context);

        $this->assertSame(1, $results->count());
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
