<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Git;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Git\G004_EnvBackupCommitted;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\GitScanResult;
use VaultCheck\Engine\ScanContext;

class G004Test extends TestCase
{
    public function test_fires_when_env_backup_was_committed(): void
    {
        $git = new GitScanResult();
        $git->gitPresent          = true;
        $git->backupEverCommitted = true;

        $context = $this->makeContext($git);
        $results = (new G004_EnvBackupCommitted())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('G004', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
    }

    public function test_clean_when_no_backup_committed(): void
    {
        $git = new GitScanResult();
        $git->gitPresent          = true;
        $git->backupEverCommitted = false;

        $context = $this->makeContext($git);
        $results = (new G004_EnvBackupCommitted())->run($context);

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
