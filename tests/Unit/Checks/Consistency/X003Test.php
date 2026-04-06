<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Consistency;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Consistency\X003_SecretOverlap;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class X003Test extends TestCase
{
    public function test_fires_when_sensitive_key_shared_between_prod_and_staging(): void
    {
        $context = $this->makeContext([
            '.env.production' => ['API_SECRET' => 'shared_secret_value'],
            '.env.staging'    => ['API_SECRET' => 'shared_secret_value'],
        ]);
        $results = (new X003_SecretOverlap())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('X003', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
    }

    public function test_clean_when_secrets_differ_between_prod_and_staging(): void
    {
        $context = $this->makeContext([
            '.env.production' => ['API_SECRET' => 'prod_secret_xyz'],
            '.env.staging'    => ['API_SECRET' => 'staging_secret_abc'],
        ]);
        $results = (new X003_SecretOverlap())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_ignores_non_sensitive_keys(): void
    {
        $context = $this->makeContext([
            '.env.production' => ['APP_NAME' => 'MyApp'],
            '.env.staging'    => ['APP_NAME' => 'MyApp'],
        ]);
        $results = (new X003_SecretOverlap())->run($context);

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
