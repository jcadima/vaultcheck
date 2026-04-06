<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Consistency;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Consistency\X001_SharedDbPassword;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class X001Test extends TestCase
{
    public function test_fires_when_db_password_shared_across_envs(): void
    {
        $context = $this->makeContext([
            '.env'         => ['DB_PASSWORD' => 'samepassword'],
            '.env.staging' => ['DB_PASSWORD' => 'samepassword'],
        ]);
        $results = (new X001_SharedDbPassword())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('X001', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
    }

    public function test_clean_when_db_passwords_differ(): void
    {
        $context = $this->makeContext([
            '.env'         => ['DB_PASSWORD' => 'prod_password_123'],
            '.env.staging' => ['DB_PASSWORD' => 'staging_password_456'],
        ]);
        $results = (new X001_SharedDbPassword())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_clean_when_only_one_env_file(): void
    {
        $context = $this->makeContext([
            '.env' => ['DB_PASSWORD' => 'samepassword'],
        ]);
        // isApplicable requires count >= 2; run() still processes but finds nothing to compare
        $results = (new X001_SharedDbPassword())->run($context);

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
