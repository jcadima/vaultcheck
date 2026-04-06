<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Codebase;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Codebase\C003_NoDefaultValue;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class C003Test extends TestCase
{
    public function test_fires_when_env_called_without_default(): void
    {
        $context = $this->makeContext([
            'APP_KEY' => [['file' => 'app/Service.php', 'line' => 5, 'hasDefault' => false]],
        ]);
        $results = (new C003_NoDefaultValue())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('C003', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_MEDIUM, $findings[0]->severity);
        $this->assertStringContainsString('APP_KEY', $findings[0]->message);
    }

    public function test_fires_only_once_per_key_even_with_multiple_usages(): void
    {
        $context = $this->makeContext([
            'DB_HOST' => [
                ['file' => 'app/A.php', 'line' => 1, 'hasDefault' => false],
                ['file' => 'app/B.php', 'line' => 2, 'hasDefault' => false],
            ],
        ]);
        $results = (new C003_NoDefaultValue())->run($context);

        $this->assertSame(1, $results->count());
    }

    public function test_clean_when_all_calls_have_defaults(): void
    {
        $context = $this->makeContext([
            'APP_KEY' => [['file' => 'config/app.php', 'line' => 5, 'hasDefault' => true]],
        ]);
        $results = (new C003_NoDefaultValue())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    private function makeContext(array $codebaseRefs): ScanContext
    {
        return new ScanContext(
            projectPath:  '/tmp/fake',
            envVars:      [],
            exampleVars:  [],
            envFiles:     [],
            multiEnvVars: [],
            isProduction: false,
            codebaseRefs: $codebaseRefs,
        );
    }
}
