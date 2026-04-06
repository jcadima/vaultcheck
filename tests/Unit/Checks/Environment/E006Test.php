<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Environment;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Environment\E006_PlaceholderValues;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class E006Test extends TestCase
{
    public function test_fires_for_placeholder_value(): void
    {
        $context = $this->makeContext(['DB_PASSWORD' => 'changeme']);
        $results = (new E006_PlaceholderValues())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('E006', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_MEDIUM, $findings[0]->severity);
    }

    public function test_fires_for_various_placeholders(): void
    {
        $placeholders = ['changeme', 'your-secret-key', 'placeholder', 'xxx', 'todo', 'secret', 'password'];
        foreach ($placeholders as $value) {
            $context = $this->makeContext(['SOME_KEY' => $value]);
            $results = (new E006_PlaceholderValues())->run($context);
            $this->assertSame(1, $results->count(), "Expected placeholder '{$value}' to trigger E006");
        }
    }

    public function test_clean_when_real_value_set(): void
    {
        $context = $this->makeContext(['DB_PASSWORD' => 'xK9#mP2$nL7qR4w!']);
        $results = (new E006_PlaceholderValues())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_ignores_empty_values(): void
    {
        $context = $this->makeContext(['DB_PASSWORD' => '']);
        $results = (new E006_PlaceholderValues())->run($context);

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
