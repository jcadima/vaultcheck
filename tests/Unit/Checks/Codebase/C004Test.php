<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Codebase;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Codebase\C004_EnvOutsideConfig;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class C004Test extends TestCase
{
    public function test_fires_when_env_called_outside_config_dir(): void
    {
        $context = $this->makeContext([
            'APP_KEY' => [['file' => '/project/app/Services/MyService.php', 'line' => 10, 'hasDefault' => false]],
        ], '/project');
        $results = (new C004_EnvOutsideConfig())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('C004', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_MEDIUM, $findings[0]->severity);
    }

    public function test_clean_when_env_called_inside_config_dir(): void
    {
        $context = $this->makeContext([
            'APP_KEY' => [['file' => '/project/config/app.php', 'line' => 5, 'hasDefault' => false]],
        ], '/project');
        $results = (new C004_EnvOutsideConfig())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_fires_for_each_usage_outside_config(): void
    {
        $context = $this->makeContext([
            'APP_KEY' => [
                ['file' => '/project/app/A.php', 'line' => 1, 'hasDefault' => false],
                ['file' => '/project/app/B.php', 'line' => 2, 'hasDefault' => false],
            ],
        ], '/project');
        $results = (new C004_EnvOutsideConfig())->run($context);

        $this->assertSame(2, $results->count());
    }

    private function makeContext(array $codebaseRefs, string $projectPath = '/project'): ScanContext
    {
        return new ScanContext(
            projectPath:  $projectPath,
            envVars:      [],
            exampleVars:  [],
            envFiles:     [],
            multiEnvVars: [],
            isProduction: false,
            codebaseRefs: $codebaseRefs,
        );
    }
}
