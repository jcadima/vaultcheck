<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Git;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Git\G003_HighEntropyInHistory;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\GitScanResult;
use VaultCheck\Engine\ScanContext;

class G003Test extends TestCase
{
    public function test_fires_for_each_high_entropy_match(): void
    {
        $git = new GitScanResult();
        $git->gitPresent     = true;
        $git->entropyMatches = [
            [
                'redacted' => 'xK9m****!vB',
                'entropy'  => 5.2,
                'commit'   => 'abc123def456',
                'file'     => '.env',
            ],
        ];

        $context = $this->makeContext($git);
        $results = (new G003_HighEntropyInHistory())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('G003', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
        $this->assertStringContainsString('5.20', $findings[0]->message);
    }

    public function test_clean_when_no_entropy_matches(): void
    {
        $git = new GitScanResult();
        $git->gitPresent     = true;
        $git->entropyMatches = [];

        $context = $this->makeContext($git);
        $results = (new G003_HighEntropyInHistory())->run($context);

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
