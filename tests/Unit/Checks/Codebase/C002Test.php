<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Codebase;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Codebase\C002_ReferencedNotDefined;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class C002Test extends TestCase
{
    public function test_fires_when_code_references_undefined_var(): void
    {
        $context = $this->makeContext(
            envVars:      ['APP_KEY' => 'abc'],
            codebaseRefs: [
                'APP_KEY'     => [['file' => 'config/app.php', 'line' => 5, 'hasDefault' => false]],
                'MISSING_KEY' => [['file' => 'app/Service.php', 'line' => 12, 'hasDefault' => false]],
            ],
        );
        $results = (new C002_ReferencedNotDefined())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('C002', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
        $this->assertStringContainsString('MISSING_KEY', $findings[0]->message);
    }

    public function test_clean_when_all_referenced_vars_defined(): void
    {
        $context = $this->makeContext(
            envVars:      ['APP_KEY' => 'abc', 'DB_HOST' => 'localhost'],
            codebaseRefs: [
                'APP_KEY' => [['file' => 'config/app.php', 'line' => 5, 'hasDefault' => false]],
                'DB_HOST' => [['file' => 'config/db.php',  'line' => 3, 'hasDefault' => false]],
            ],
        );
        $results = (new C002_ReferencedNotDefined())->run($context);

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
