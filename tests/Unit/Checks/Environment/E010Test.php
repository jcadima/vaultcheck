<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Environment;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Environment\E010_WindowsLineEndings;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;
use VaultCheck\Parsers\EnvFileParser;

class E010Test extends TestCase
{
    private string $tmpDir;
    private string $envFile;

    protected function setUp(): void
    {
        $this->tmpDir  = sys_get_temp_dir() . '/vaultcheck_e010_' . uniqid();
        mkdir($this->tmpDir, 0700, true);
        $this->envFile = $this->tmpDir . '/.env';
    }

    protected function tearDown(): void
    {
        if (is_file($this->envFile)) {
            unlink($this->envFile);
        }
        rmdir($this->tmpDir);
    }

    public function test_fires_when_crlf_line_endings(): void
    {
        file_put_contents($this->envFile, "APP_NAME=Test\r\nAPP_ENV=local\r\n");
        $context = $this->makeContext($this->tmpDir);
        $results = (new E010_WindowsLineEndings(new EnvFileParser()))->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('E010', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_LOW, $findings[0]->severity);
    }

    public function test_fires_when_cr_only_line_endings(): void
    {
        file_put_contents($this->envFile, "APP_NAME=Test\rAPP_ENV=local\r");
        $context = $this->makeContext($this->tmpDir);
        $results = (new E010_WindowsLineEndings(new EnvFileParser()))->run($context);

        $this->assertSame(1, $results->count());
    }

    public function test_clean_when_unix_line_endings(): void
    {
        file_put_contents($this->envFile, "APP_NAME=Test\nAPP_ENV=local\n");
        $context = $this->makeContext($this->tmpDir);
        $results = (new E010_WindowsLineEndings(new EnvFileParser()))->run($context);

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
