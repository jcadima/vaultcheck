<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Environment;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Environment\E013_LogLevelNotSet;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class E013Test extends TestCase
{
    public function test_fires_when_no_log_level_key_set(): void
    {
        $context = $this->makeContext(['APP_NAME' => 'Test']);
        $results = (new E013_LogLevelNotSet())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('E013', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_LOW, $findings[0]->severity);
    }

    public function test_clean_when_log_level_set(): void
    {
        $context = $this->makeContext(['LOG_LEVEL' => 'error']);
        $results = (new E013_LogLevelNotSet())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_clean_when_log_channel_set(): void
    {
        $context = $this->makeContext(['LOG_CHANNEL' => 'stack']);
        $results = (new E013_LogLevelNotSet())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_fires_when_log_level_key_present_but_empty(): void
    {
        $context = $this->makeContext(['LOG_LEVEL' => '']);
        $results = (new E013_LogLevelNotSet())->run($context);

        $this->assertSame(1, $results->count());
    }

    private function makeContext(array $envVars): ScanContext
    {
        return new ScanContext(
            projectPath:  '/tmp/fake',
            envVars:      $envVars,
            exampleVars:  [],
            envFiles:     [],
            multiEnvVars: [],
            isProduction: false,
        );
    }
}
