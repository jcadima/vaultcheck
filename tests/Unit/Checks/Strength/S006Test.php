<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Strength;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Strength\S006_PasswordEqualsUsername;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class S006Test extends TestCase
{
    public function test_fires_when_db_password_equals_username(): void
    {
        $context = $this->makeContext(['DB_USERNAME' => 'admin', 'DB_PASSWORD' => 'admin']);
        $results = (new S006_PasswordEqualsUsername())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('S006', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
    }

    public function test_fires_for_mail_credentials(): void
    {
        $context = $this->makeContext(['MAIL_USERNAME' => 'user@example.com', 'MAIL_PASSWORD' => 'user@example.com']);
        $results = (new S006_PasswordEqualsUsername())->run($context);

        $this->assertSame(1, $results->count());
    }

    public function test_clean_when_password_differs_from_username(): void
    {
        $context = $this->makeContext(['DB_USERNAME' => 'myapp', 'DB_PASSWORD' => 'xK9#mP2$nL7!']);
        $results = (new S006_PasswordEqualsUsername())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_clean_when_credentials_not_set(): void
    {
        $context = $this->makeContext(['APP_KEY' => 'abc']);
        $results = (new S006_PasswordEqualsUsername())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    private function makeContext(array $envVars): ScanContext
    {
        return new ScanContext(
            projectPath:  '/tmp/fake',
            envVars:      $envVars,
            exampleVars:  [],
            envFiles:     [],
            multiEnvVars: [],
            isProduction: false,
        );
    }
}
