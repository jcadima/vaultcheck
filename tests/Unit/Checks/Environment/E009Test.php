<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Environment;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Environment\E009_LocalhostDbProduction;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class E009Test extends TestCase
{
    public function test_fires_when_localhost_db_in_production(): void
    {
        $context = $this->makeContext(['DB_HOST' => 'localhost']);
        $results = (new E009_LocalhostDbProduction())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('E009', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
    }

    public function test_fires_for_127_0_0_1(): void
    {
        $context = $this->makeContext(['DB_HOST' => '127.0.0.1']);
        $results = (new E009_LocalhostDbProduction())->run($context);

        $this->assertSame(1, $results->count());
    }

    public function test_clean_when_real_production_host(): void
    {
        $context = $this->makeContext(['DB_HOST' => 'prod.db.example.com']);
        $results = (new E009_LocalhostDbProduction())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_clean_when_db_host_not_set(): void
    {
        $context = $this->makeContext([]);
        $results = (new E009_LocalhostDbProduction())->run($context);

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
            isProduction: true,
        );
    }
}
