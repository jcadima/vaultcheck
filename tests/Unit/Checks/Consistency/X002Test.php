<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Consistency;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Consistency\X002_SharedAppKey;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class X002Test extends TestCase
{
    public function test_fires_when_app_key_shared(): void
    {
        $context = $this->makeContext([
            '.env'         => ['APP_KEY' => 'base64:sharedKey12345678901234567890=='],
            '.env.staging' => ['APP_KEY' => 'base64:sharedKey12345678901234567890=='],
        ]);
        $results = (new X002_SharedAppKey())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('X002', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_CRITICAL, $findings[0]->severity);
    }

    public function test_clean_when_app_keys_differ(): void
    {
        $context = $this->makeContext([
            '.env'         => ['APP_KEY' => 'base64:prodKeyAAAAAAAAAAAAAAAAAAAAA=='],
            '.env.staging' => ['APP_KEY' => 'base64:stagingKeyBBBBBBBBBBBBBBBBBB=='],
        ]);
        $results = (new X002_SharedAppKey())->run($context);

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
