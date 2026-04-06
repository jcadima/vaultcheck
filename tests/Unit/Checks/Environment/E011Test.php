<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Environment;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Environment\E011_DuplicateKeys;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;
use VaultCheck\Parsers\EnvFileParser;

class E011Test extends TestCase
{
    private string $tmpDir;
    private string $envFile;

    protected function setUp(): void
    {
        $this->tmpDir  = sys_get_temp_dir() . '/vaultcheck_e011_' . uniqid();
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

    public function test_fires_when_duplicate_key_present(): void
    {
        file_put_contents($this->envFile, "APP_KEY=first\nDB_HOST=localhost\nAPP_KEY=second\n");
        $context = $this->makeContext($this->tmpDir);
        $results = (new E011_DuplicateKeys(new EnvFileParser()))->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('E011', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_MEDIUM, $findings[0]->severity);
        $this->assertStringContainsString('APP_KEY', $findings[0]->message);
    }

    public function test_fires_once_per_duplicate_occurrence(): void
    {
        file_put_contents($this->envFile, "KEY=a\nKEY=b\nKEY=c\n");
        $context = $this->makeContext($this->tmpDir);
        $results = (new E011_DuplicateKeys(new EnvFileParser()))->run($context);

        $this->assertSame(2, $results->count());
    }

    public function test_clean_when_no_duplicates(): void
    {
        file_put_contents($this->envFile, "APP_KEY=abc\nDB_HOST=localhost\n");
        $context = $this->makeContext($this->tmpDir);
        $results = (new E011_DuplicateKeys(new EnvFileParser()))->run($context);

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
