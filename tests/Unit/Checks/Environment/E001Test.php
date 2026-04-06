<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Environment;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Environment\E001_EnvFileMissing;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class E001Test extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/vaultcheck_e001_' . uniqid();
        mkdir($this->tmpDir, 0700, true);
    }

    protected function tearDown(): void
    {
        $env = $this->tmpDir . '/.env';
        if (is_file($env)) {
            unlink($env);
        }
        rmdir($this->tmpDir);
    }

    public function test_fires_when_env_file_missing(): void
    {
        $context = $this->makeContext($this->tmpDir);
        $results = (new E001_EnvFileMissing())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('E001', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
    }

    public function test_clean_when_env_file_exists(): void
    {
        file_put_contents($this->tmpDir . '/.env', 'APP_NAME=Test');
        $context = $this->makeContext($this->tmpDir);
        $results = (new E001_EnvFileMissing())->run($context);

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
