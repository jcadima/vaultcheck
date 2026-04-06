<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Git;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Git\G005_SecretsInConfigDir;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\GitScanResult;
use VaultCheck\Engine\ScanContext;

class G005Test extends TestCase
{
    public function test_fires_for_each_credential_in_config_history(): void
    {
        $git = new GitScanResult();
        $git->gitPresent        = true;
        $git->configCredentials = [
            [
                'key'      => 'DB_PASSWORD',
                'redacted' => 'pa****rd',
                'commit'   => 'abc123def456',
                'file'     => 'config/database.php',
            ],
        ];

        $context = $this->makeContext($git);
        $results = (new G005_SecretsInConfigDir())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('G005', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
        $this->assertStringContainsString('DB_PASSWORD', $findings[0]->message);
    }

    public function test_clean_when_no_credentials_in_config_history(): void
    {
        $git = new GitScanResult();
        $git->gitPresent        = true;
        $git->configCredentials = [];

        $context = $this->makeContext($git);
        $results = (new G005_SecretsInConfigDir())->run($context);

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
