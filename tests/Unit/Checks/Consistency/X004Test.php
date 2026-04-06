<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Consistency;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Consistency\X004_EnvLabelMismatch;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class X004Test extends TestCase
{
    public function test_fires_when_staging_file_has_production_app_env(): void
    {
        $context = $this->makeContext([
            '.env'         => ['APP_ENV' => 'local'],
            '.env.staging' => ['APP_ENV' => 'production'],
        ]);
        $results = (new X004_EnvLabelMismatch())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('X004', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_MEDIUM, $findings[0]->severity);
        $this->assertStringContainsString('.env.staging', $findings[0]->message);
    }

    public function test_clean_when_staging_file_has_correct_app_env(): void
    {
        $context = $this->makeContext([
            '.env'         => ['APP_ENV' => 'local'],
            '.env.staging' => ['APP_ENV' => 'staging'],
        ]);
        $results = (new X004_EnvLabelMismatch())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_ignores_root_env_file(): void
    {
        // .env itself has no expected suffix
        $context = $this->makeContext([
            '.env'         => ['APP_ENV' => 'production'],
            '.env.staging' => ['APP_ENV' => 'staging'],
        ]);
        $results = (new X004_EnvLabelMismatch())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    private function makeContext(array $multiEnvVars): ScanContext
    {
        return new ScanContext(
            projectPath:  '/tmp/fake',
            envVars:      [],
            exampleVars:  [],
            envFiles:     [],
            multiEnvVars: $multiEnvVars,
            isProduction: false,
        );
    }
}
