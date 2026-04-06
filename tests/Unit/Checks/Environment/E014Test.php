<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Environment;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Environment\E014_FileDrivers;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class E014Test extends TestCase
{
    public function test_fires_when_file_cache_driver_in_production(): void
    {
        $context = $this->makeContext(['CACHE_DRIVER' => 'file'], isProduction: true);
        $results = (new E014_FileDrivers())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('E014', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_MEDIUM, $findings[0]->severity);
    }

    public function test_fires_for_sync_queue_in_production(): void
    {
        $context = $this->makeContext(['QUEUE_CONNECTION' => 'sync'], isProduction: true);
        $results = (new E014_FileDrivers())->run($context);

        $this->assertSame(1, $results->count());
    }

    public function test_clean_when_production_driver(): void
    {
        $context = $this->makeContext(['CACHE_DRIVER' => 'redis'], isProduction: true);
        $results = (new E014_FileDrivers())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    private function makeContext(array $envVars, bool $isProduction): ScanContext
    {
        return new ScanContext(
            projectPath:  '/tmp/fake',
            envVars:      $envVars,
            exampleVars:  [],
            envFiles:     [],
            multiEnvVars: [],
            isProduction: $isProduction,
        );
    }
}
