<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Codebase;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Codebase\C005_CaseInconsistency;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class C005Test extends TestCase
{
    public function test_fires_when_casing_differs_between_env_and_code(): void
    {
        $context = $this->makeContext(
            envVars:      ['DB_HOST' => 'localhost'],
            codebaseRefs: ['db_host' => [['file' => 'app/Service.php', 'line' => 5, 'hasDefault' => false]]],
        );
        $results = (new C005_CaseInconsistency())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('C005', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_LOW, $findings[0]->severity);
        $this->assertStringContainsString('db_host', $findings[0]->message);
        $this->assertStringContainsString('DB_HOST', $findings[0]->message);
    }

    public function test_clean_when_casing_matches(): void
    {
        $context = $this->makeContext(
            envVars:      ['DB_HOST' => 'localhost'],
            codebaseRefs: ['DB_HOST' => [['file' => 'config/db.php', 'line' => 3, 'hasDefault' => false]]],
        );
        $results = (new C005_CaseInconsistency())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_clean_when_ref_key_not_in_env(): void
    {
        // Key not in .env at all — C002 would catch this, not C005
        $context = $this->makeContext(
            envVars:      ['APP_KEY' => 'abc'],
            codebaseRefs: ['UNKNOWN_KEY' => [['file' => 'app/X.php', 'line' => 1, 'hasDefault' => false]]],
        );
        $results = (new C005_CaseInconsistency())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    private function makeContext(array $envVars, array $codebaseRefs): ScanContext
    {
        return new ScanContext(
            projectPath:  '/tmp/fake',
            envVars:      $envVars,
            exampleVars:  [],
            envFiles:     [],
            multiEnvVars: [],
            isProduction: false,
            codebaseRefs: $codebaseRefs,
        );
    }
}
