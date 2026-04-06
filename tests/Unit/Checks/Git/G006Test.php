<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Git;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Git\G006_EnvMissingFromGitignore;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\GitScanResult;
use VaultCheck\Engine\ScanContext;

class G006Test extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/vaultcheck_g006_' . uniqid();
        mkdir($this->tmpDir, 0700, true);
    }

    protected function tearDown(): void
    {
        $gitignore = $this->tmpDir . '/.gitignore';
        if (is_file($gitignore)) {
            unlink($gitignore);
        }
        rmdir($this->tmpDir);
    }

    public function test_fires_when_env_not_in_gitignore_via_git_scan_result(): void
    {
        $git = new GitScanResult();
        $git->gitPresent    = true;
        $git->envInGitignore = false;

        $context = $this->makeContext($git);
        $results = (new G006_EnvMissingFromGitignore())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('G006', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_CRITICAL, $findings[0]->severity);
    }

    public function test_clean_when_env_in_gitignore_via_git_scan_result(): void
    {
        $git = new GitScanResult();
        $git->gitPresent    = true;
        $git->envInGitignore = true;

        $context = $this->makeContext($git);
        $results = (new G006_EnvMissingFromGitignore())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_fires_when_env_not_in_gitignore_file_directly(): void
    {
        file_put_contents($this->tmpDir . '/.gitignore', "vendor/\nnode_modules/\n");

        $context = $this->makeContextWithPath($this->tmpDir);
        $results = (new G006_EnvMissingFromGitignore())->run($context);

        $this->assertSame(1, $results->count());
    }

    public function test_clean_when_env_in_gitignore_file(): void
    {
        file_put_contents($this->tmpDir . '/.gitignore', "vendor/\n.env\n");

        $context = $this->makeContextWithPath($this->tmpDir);
        $results = (new G006_EnvMissingFromGitignore())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    private function makeContext(GitScanResult $git): ScanContext
    {
        return new ScanContext(
            projectPath:   '/tmp/fake',
            envVars:       [],
            exampleVars:   [],
            envFiles:      [],
            multiEnvVars:  [],
            isProduction:  false,
            gitScanResult: $git,
        );
    }

    private function makeContextWithPath(string $projectPath): ScanContext
    {
        return new ScanContext(
            projectPath:   $projectPath,
            envVars:       [],
            exampleVars:   [],
            envFiles:      [],
            multiEnvVars:  [],
            isProduction:  false,
            gitScanResult: null,
        );
    }
}
