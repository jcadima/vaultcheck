<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Environment;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Environment\E002_ExampleMissing;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class E002Test extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/vaultcheck_e002_' . uniqid();
        mkdir($this->tmpDir, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (['.env', '.env.example'] as $file) {
            $path = $this->tmpDir . '/' . $file;
            if (is_file($path)) {
                unlink($path);
            }
        }
        rmdir($this->tmpDir);
    }

    public function test_fires_when_example_file_missing(): void
    {
        $context = $this->makeContext($this->tmpDir);
        $results = (new E002_ExampleMissing())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('E002', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_MEDIUM, $findings[0]->severity);
    }

    public function test_clean_when_example_file_exists(): void
    {
        file_put_contents($this->tmpDir . '/.env.example', 'APP_NAME=');
        $context = $this->makeContext($this->tmpDir);
        $results = (new E002_ExampleMissing())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    private function makeContext(string $projectPath): ScanContext
    {
        return new ScanContext(
            projectPath:  $projectPath,
            envVars:      [],
            exampleVars:  [],
            envFiles:     [],
            multiEnvVars: [],
            isProduction: false,
        );
    }
}
