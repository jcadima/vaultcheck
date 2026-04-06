<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Codebase;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Codebase\C001_UnusedEnvVar;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class C001Test extends TestCase
{
    public function test_fires_when_env_var_unreferenced_in_codebase(): void
    {
        $context = $this->makeContext(
            envVars:      ['APP_KEY' => 'abc', 'DEAD_VAR' => 'xyz'],
            codebaseRefs: ['APP_KEY' => [['file' => 'config/app.php', 'line' => 5, 'hasDefault' => false]]],
        );
        $results = (new C001_UnusedEnvVar())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('C001', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_LOW, $findings[0]->severity);
        $this->assertStringContainsString('DEAD_VAR', $findings[0]->message);
    }

    public function test_clean_when_all_env_vars_referenced(): void
    {
        $context = $this->makeContext(
            envVars:      ['APP_KEY' => 'abc'],
            codebaseRefs: ['APP_KEY' => [['file' => 'config/app.php', 'line' => 5, 'hasDefault' => false]]],
        );
        $results = (new C001_UnusedEnvVar())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_fires_for_each_unreferenced_var(): void
    {
        $context = $this->makeContext(
            envVars:      ['A' => '1', 'B' => '2', 'C' => '3'],
            codebaseRefs: [],
        );
        $results = (new C001_UnusedEnvVar())->run($context);

        $this->assertSame(3, $results->count());
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
