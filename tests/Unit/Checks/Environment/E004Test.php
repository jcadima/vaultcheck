<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Environment;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Environment\E004_OrphanedKeys;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class E004Test extends TestCase
{
    public function test_fires_when_example_key_absent_from_env(): void
    {
        $context = $this->makeContext(
            envVars:     ['APP_KEY' => 'abc'],
            exampleVars: ['APP_KEY' => '', 'ORPHANED_KEY' => ''],
        );
        $results = (new E004_OrphanedKeys())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('E004', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_LOW, $findings[0]->severity);
        $this->assertStringContainsString('ORPHANED_KEY', $findings[0]->message);
    }

    public function test_clean_when_all_example_keys_in_env(): void
    {
        $context = $this->makeContext(
            envVars:     ['APP_KEY' => 'abc', 'DB_HOST' => 'host'],
            exampleVars: ['APP_KEY' => '',    'DB_HOST' => ''],
        );
        $results = (new E004_OrphanedKeys())->run($context);

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
