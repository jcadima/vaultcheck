<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Strength;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Strength\S005_JwtSecretTooShort;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class S005Test extends TestCase
{
    public function test_fires_when_jwt_secret_too_short(): void
    {
        $context = $this->makeContext(['JWT_SECRET' => 'short-jwt']); // 9 chars < 32
        $results = (new S005_JwtSecretTooShort())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('S005', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
    }

    public function test_clean_when_jwt_secret_long_enough(): void
    {
        $context = $this->makeContext(['JWT_SECRET' => 'this-is-a-very-long-jwt-secret-key-that-is-secure']);
        $results = (new S005_JwtSecretTooShort())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_fires_for_jwt_key_variant(): void
    {
        $context = $this->makeContext(['JWT_KEY' => 'tooshort']);
        $results = (new S005_JwtSecretTooShort())->run($context);

        $this->assertSame(1, $results->count());
    }

    public function test_clean_when_jwt_key_not_present(): void
    {
        $context = $this->makeContext(['APP_KEY' => 'some-other-key']);
        $results = (new S005_JwtSecretTooShort())->run($context);

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
