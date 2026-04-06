<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Permissions;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Permissions\P003_PermissionOwnership;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class P003Test extends TestCase
{
    private string $tmpDir;
    private string $envFile;

    protected function setUp(): void
    {
        $this->tmpDir  = sys_get_temp_dir() . '/vaultcheck_p003_' . uniqid();
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

    public function test_fires_when_env_is_group_writable(): void
    {
        chmod($this->envFile, 0660); // group-writable (bit 0x0010)
        clearstatcache();

        $context = $this->makeContext($this->tmpDir);
        $results = (new P003_PermissionOwnership())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('P003', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_MEDIUM, $findings[0]->severity);
    }

    public function test_clean_when_env_not_group_writable(): void
    {
        chmod($this->envFile, 0640); // group-readable but not writable
        clearstatcache();

        $context = $this->makeContext($this->tmpDir);
        $results = (new P003_PermissionOwnership())->run($context);

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
