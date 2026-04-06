<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Permissions;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Permissions\P002_WorldWritable;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class P002Test extends TestCase
{
    private string $tmpDir;
    private string $envFile;

    protected function setUp(): void
    {
        $this->tmpDir  = sys_get_temp_dir() . '/vaultcheck_p002_' . uniqid();
        mkdir($this->tmpDir, 0700, true);
        $this->envFile = $this->tmpDir . '/.env';
        file_put_contents($this->envFile, 'APP_KEY=abc');
    }

    protected function tearDown(): void
    {
        if (is_file($this->envFile)) {
            chmod($this->envFile, 0600);
            unlink($this->envFile);
        }
        rmdir($this->tmpDir);
    }

    public function test_fires_when_env_is_world_writable(): void
    {
        chmod($this->envFile, 0666); // world-writable
        clearstatcache();

        $context = $this->makeContext($this->tmpDir);
        $results = (new P002_WorldWritable())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('P002', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_CRITICAL, $findings[0]->severity);
    }

    public function test_clean_when_env_is_not_world_writable(): void
    {
        chmod($this->envFile, 0640); // no world write
        clearstatcache();

        $context = $this->makeContext($this->tmpDir);
        $results = (new P002_WorldWritable())->run($context);

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
