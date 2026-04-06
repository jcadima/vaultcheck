<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Git;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Git\G008_UnrotatedLeak;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\GitScanResult;
use VaultCheck\Engine\ScanContext;

class G008Test extends TestCase
{
    public function test_fires_once_per_unrotated_key(): void
    {
        $git = new GitScanResult();
        $git->gitPresent               = true;
        $git->currentSecretsInHistory  = [
            'STRIPE_KEY'  => true,
            'DB_PASSWORD' => true,
        ];

        $context = $this->makeContext($git);
        $results = (new G008_UnrotatedLeak())->run($context);

        $this->assertSame(2, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('G008', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_CRITICAL, $findings[0]->severity);
    }

    public function test_message_contains_the_key_name(): void
    {
        $git = new GitScanResult();
        $git->gitPresent              = true;
        $git->currentSecretsInHistory = ['API_SECRET' => true];

        $context = $this->makeContext($git);
        $results = (new G008_UnrotatedLeak())->run($context);

        $findings = iterator_to_array($results->getIterator());
        $this->assertStringContainsString('API_SECRET', $findings[0]->message);
    }

    public function test_clean_when_no_unrotated_secrets(): void
    {
        $git = new GitScanResult();
        $git->gitPresent              = true;
        $git->currentSecretsInHistory = [];

        $context = $this->makeContext($git);
        $results = (new G008_UnrotatedLeak())->run($context);

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
}
