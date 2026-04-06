<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Strength;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Strength\S003_KnownWeakValues;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class S003Test extends TestCase
{
    public function test_fires_for_known_weak_password(): void
    {
        $context = $this->makeContext(['DB_PASSWORD' => 'password']);
        $results = (new S003_KnownWeakValues())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('S003', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
    }

    public function test_fires_for_various_known_weak_values(): void
    {
        $weakValues = ['admin', 'secret', 'changeme', 'qwerty', '123456'];
        foreach ($weakValues as $value) {
            $context = $this->makeContext(['DB_PASSWORD' => $value]);
            $results = (new S003_KnownWeakValues())->run($context);
            $this->assertSame(1, $results->count(), "Expected '{$value}' to trigger S003");
        }
    }

    public function test_case_insensitive_matching(): void
    {
        $context = $this->makeContext(['DB_PASSWORD' => 'PASSWORD']);
        $results = (new S003_KnownWeakValues())->run($context);

        $this->assertSame(1, $results->count());
    }

    public function test_clean_when_strong_password(): void
    {
        $context = $this->makeContext(['DB_PASSWORD' => 'xK9#mP2$nL7qR4w!vB3z']);
        $results = (new S003_KnownWeakValues())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_ignores_non_sensitive_keys(): void
    {
        $context = $this->makeContext(['APP_NAME' => 'admin']);
        $results = (new S003_KnownWeakValues())->run($context);

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
