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

    public function test_downgrades_to_medium_when_app_code_has_defaults(): void
    {
        // App code (not config/) with all defaults → MEDIUM
        $context = $this->makeContext(
            envVars:      [],
            codebaseRefs: [
                'OPTIONAL_KEY' => [['file' => 'app/Service.php', 'line' => 10, 'hasDefault' => true]],
            ],
        );
        $results  = (new C002_ReferencedNotDefined())->run($context);
        $findings = iterator_to_array($results->getIterator());

        $this->assertSame(1, $results->count());
        $this->assertSame(Finding::SEVERITY_MEDIUM, $findings[0]->severity);
    }

    public function test_stays_high_when_app_code_lacks_default(): void
    {
        $context = $this->makeContext(
            envVars:      [],
            codebaseRefs: [
                'REQUIRED_KEY' => [
                    ['file' => 'config/app.php', 'line' => 5,  'hasDefault' => true],
                    ['file' => 'app/Service.php', 'line' => 20, 'hasDefault' => false],
                ],
            ],
        );
        $results  = (new C002_ReferencedNotDefined())->run($context);
        $findings = iterator_to_array($results->getIterator());

        $this->assertSame(1, $results->count());
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
    }

    public function test_downgrades_to_low_when_config_only_and_no_defaults(): void
    {
        $context = $this->makeContext(
            envVars:      [],
            codebaseRefs: [
                'MEMCACHED_HOST' => [['file' => 'config/cache.php', 'line' => 30, 'hasDefault' => false]],
            ],
        );
        $results  = (new C002_ReferencedNotDefined())->run($context);
        $findings = iterator_to_array($results->getIterator());

        $this->assertSame(1, $results->count());
        $this->assertSame(Finding::SEVERITY_LOW, $findings[0]->severity);
    }

    public function test_downgrades_to_low_when_config_only_and_all_have_defaults(): void
    {
        $context = $this->makeContext(
            envVars:      [],
            codebaseRefs: [
                'OPTIONAL_KEY' => [['file' => 'config/cache.php', 'line' => 10, 'hasDefault' => true]],
            ],
        );
        $results  = (new C002_ReferencedNotDefined())->run($context);
        $findings = iterator_to_array($results->getIterator());

        $this->assertSame(1, $results->count());
        $this->assertSame(Finding::SEVERITY_LOW, $findings[0]->severity);
    }

    public function test_downgrades_to_low_with_absolute_config_path(): void
    {
        // Real-world paths from CodebaseScanner use absolute paths via getRealPath()
        $context = $this->makeContext(
            envVars:      [],
            codebaseRefs: [
                'MEMCACHED_HOST' => [['file' => '/var/www/html/config/cache.php', 'line' => 30, 'hasDefault' => false]],
            ],
        );
        $results  = (new C002_ReferencedNotDefined())->run($context);
        $findings = iterator_to_array($results->getIterator());

        $this->assertSame(1, $results->count());
        $this->assertSame(Finding::SEVERITY_LOW, $findings[0]->severity);
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
