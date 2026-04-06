<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Permissions;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Permissions\P004_EnvInPublicDir;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class P004Test extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/vaultcheck_p004_' . uniqid();
        mkdir($this->tmpDir, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (['public', 'web', 'webroot'] as $dir) {
            $envPath = $this->tmpDir . '/' . $dir . '/.env';
            if (is_file($envPath)) {
                unlink($envPath);
                rmdir($this->tmpDir . '/' . $dir);
            }
        }
        rmdir($this->tmpDir);
    }

    public function test_fires_when_env_in_public_dir(): void
    {
        mkdir($this->tmpDir . '/public', 0700, true);
        file_put_contents($this->tmpDir . '/public/.env', 'SECRET=exposed');

        $context = $this->makeContext($this->tmpDir);
        $results = (new P004_EnvInPublicDir())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('P004', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_CRITICAL, $findings[0]->severity);
    }

    public function test_fires_when_env_in_web_dir(): void
    {
        mkdir($this->tmpDir . '/web', 0700, true);
        file_put_contents($this->tmpDir . '/web/.env', 'SECRET=exposed');

        $context = $this->makeContext($this->tmpDir);
        $results = (new P004_EnvInPublicDir())->run($context);

        $this->assertSame(1, $results->count());
    }

    public function test_clean_when_no_env_in_public_dirs(): void
    {
        $context = $this->makeContext($this->tmpDir);
        $results = (new P004_EnvInPublicDir())->run($context);

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
