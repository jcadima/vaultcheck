<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Git;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Git\G001_EnvCommittedToGit;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\GitScanResult;
use VaultCheck\Engine\ScanContext;

class G001Test extends TestCase
{
    public function test_fires_when_env_was_ever_committed(): void
    {
        $git = new GitScanResult();
        $git->gitPresent       = true;
        $git->envEverCommitted = true;

        $context = $this->makeContext($git);
        $results = (new G001_EnvCommittedToGit())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('G001', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_CRITICAL, $findings[0]->severity);
    }

    public function test_clean_when_env_never_committed(): void
    {
        $git = new GitScanResult();
        $git->gitPresent       = true;
        $git->envEverCommitted = false;

        $context = $this->makeContext($git);
        $results = (new G001_EnvCommittedToGit())->run($context);

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
