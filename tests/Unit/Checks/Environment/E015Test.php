<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Environment;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Environment\E015_BackupFiles;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class E015Test extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/vaultcheck_e015_' . uniqid();
        mkdir($this->tmpDir, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (['.env.bak', '.env.backup', '.env.old', '.env.orig'] as $file) {
            $path = $this->tmpDir . '/' . $file;
            if (is_file($path)) {
                unlink($path);
            }
        }
        rmdir($this->tmpDir);
    }

    public function test_fires_when_env_bak_exists(): void
    {
        file_put_contents($this->tmpDir . '/.env.bak', 'SECRET=exposed');
        $context = $this->makeContext($this->tmpDir);
        $results = (new E015_BackupFiles())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('E015', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
    }

    public function test_fires_when_env_old_exists(): void
    {
        file_put_contents($this->tmpDir . '/.env.old', 'SECRET=exposed');
        $context = $this->makeContext($this->tmpDir);
        $results = (new E015_BackupFiles())->run($context);

        $this->assertSame(1, $results->count());
    }

    public function test_fires_once_per_backup_file(): void
    {
        file_put_contents($this->tmpDir . '/.env.bak',    'a');
        file_put_contents($this->tmpDir . '/.env.backup', 'b');
        $context = $this->makeContext($this->tmpDir);
        $results = (new E015_BackupFiles())->run($context);

        $this->assertSame(2, $results->count());
    }

    public function test_clean_when_no_backup_files(): void
    {
        $context = $this->makeContext($this->tmpDir);
        $results = (new E015_BackupFiles())->run($context);

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
