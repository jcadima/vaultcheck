<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Consistency;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Consistency\X005_UndocumentedStagingConfig;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class X005Test extends TestCase
{
    public function test_fires_when_staging_key_not_in_example(): void
    {
        $context = $this->makeContext(
            exampleVars:  ['APP_KEY' => ''],
            multiEnvVars: [
                '.env'         => ['APP_KEY' => 'abc'],
                '.env.staging' => ['APP_KEY' => 'abc', 'STAGING_ONLY_VAR' => 'xyz'],
            ],
        );
        $results = (new X005_UndocumentedStagingConfig())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('X005', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_LOW, $findings[0]->severity);
        $this->assertStringContainsString('STAGING_ONLY_VAR', $findings[0]->message);
    }

    public function test_clean_when_all_staging_keys_in_example(): void
    {
        $context = $this->makeContext(
            exampleVars:  ['APP_KEY' => '', 'FEATURE_FLAG' => ''],
            multiEnvVars: [
                '.env'         => ['APP_KEY' => 'abc'],
                '.env.staging' => ['APP_KEY' => 'abc', 'FEATURE_FLAG' => 'true'],
            ],
        );
        $results = (new X005_UndocumentedStagingConfig())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    private function makeContext(array $exampleVars, array $multiEnvVars): ScanContext
    {
        return new ScanContext(
            projectPath:  '/tmp/fake',
            envVars:      [],
            exampleVars:  $exampleVars,
            envFiles:     [],
            multiEnvVars: $multiEnvVars,
            isProduction: false,
        );
    }
}
