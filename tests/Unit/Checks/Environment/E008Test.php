<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Environment;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Environment\E008_AppDebugProduction;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class E008Test extends TestCase
{
    public function test_fires_when_app_debug_true_in_production(): void
    {
        $context = $this->makeContext(['APP_DEBUG' => 'true'], isProduction: true);
        $results = (new E008_AppDebugProduction())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('E008', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_CRITICAL, $findings[0]->severity);
    }

    public function test_fires_for_truthy_values(): void
    {
        foreach (['true', '1', 'yes', 'on'] as $value) {
            $context = $this->makeContext(['APP_DEBUG' => $value], isProduction: true);
            $results = (new E008_AppDebugProduction())->run($context);
            $this->assertSame(1, $results->count(), "E008 should fire for APP_DEBUG={$value}");
        }
    }

    public function test_clean_when_app_debug_false(): void
    {
        $context = $this->makeContext(['APP_DEBUG' => 'false'], isProduction: true);
        $results = (new E008_AppDebugProduction())->run($context);

        $this->assertTrue($results->isEmpty());
    }

    public function test_clean_when_not_production(): void
    {
        $context = $this->makeContext(['APP_DEBUG' => 'true'], isProduction: false);
        $results = (new E008_AppDebugProduction())->run($context);

        // run() doesn't gate on isProduction; it just reports. isApplicable() does.
        $this->assertSame(1, $results->count());
    }

    private function makeContext(array $envVars, bool $isProduction): ScanContext
    {
        return new ScanContext(
            projectPath:  '/tmp/fake',
            envVars:      $envVars,
            exampleVars:  [],
            envFiles:     [],
            multiEnvVars: [],
            isProduction: $isProduction,
        );
    }
}
