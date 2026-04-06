<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Git;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Git\G002_SecretPatternFound;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\GitScanResult;
use VaultCheck\Engine\ScanContext;

class G002Test extends TestCase
{
    public function test_fires_once_per_pattern_match(): void
    {
        $git = new GitScanResult();
        $git->gitPresent    = true;
        $git->patternMatches = [
            [
                'pattern' => 'Stripe Secret Key',
                'service' => 'Stripe',
                'redacted' => 'sk_l****no',
                'commit'  => 'abc123def456',
                'file'    => '.env',
            ],
            [
                'pattern' => 'AWS Access Key',
                'service' => 'AWS',
                'redacted' => 'AKIA****XYZ',
                'commit'  => 'def789ghi012',
                'file'    => 'config/services.php',
            ],
        ];

        $context = $this->makeContext($git);
        $results = (new G002_SecretPatternFound())->run($context);

        $this->assertSame(2, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('G002', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_CRITICAL, $findings[0]->severity);
    }

    public function test_clean_when_no_pattern_matches(): void
    {
        $git = new GitScanResult();
        $git->gitPresent    = true;
        $git->patternMatches = [];

        $context = $this->makeContext($git);
        $results = (new G002_SecretPatternFound())->run($context);

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
