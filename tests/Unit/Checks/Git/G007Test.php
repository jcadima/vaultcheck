<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Git;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Git\G007_EnvCommittedBeforeIgnore;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\GitScanResult;
use VaultCheck\Engine\ScanContext;

class G007Test extends TestCase
{
    public function test_fires_when_env_committed_before_gitignore(): void
    {
        $git = new GitScanResult();
        $git->gitPresent               = true;
        $git->envCommittedBeforeIgnore = true;

        $context = $this->makeContext($git);
        $results = (new G007_EnvCommittedBeforeIgnore())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('G007', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
    }

    public function test_clean_when_env_not_committed_before_gitignore(): void
    {
        $git = new GitScanResult();
        $git->gitPresent               = true;
        $git->envCommittedBeforeIgnore = false;

        $context = $this->makeContext($git);
        $results = (new G007_EnvCommittedBeforeIgnore())->run($context);

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
