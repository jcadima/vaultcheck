<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Strength;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Strength\S004_AppKeyNoPrefix;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class S004Test extends TestCase
{
    public function test_fires_when_app_key_lacks_base64_prefix(): void
    {
        $context = $this->makeContext(['APP_KEY' => 'some-random-key-without-prefix-here']);
        $results = (new S004_AppKeyNoPrefix())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('S004', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_MEDIUM, $findings[0]->severity);
    }

    public function test_not_applicable_when_app_key_has_base64_prefix(): void
    {
        $context = $this->makeContext(['APP_KEY' => 'base64:dGhpcyBpcyBhIHRlc3Qga2V5IGZvciBsYXJhdmVs']);
        $check   = new S004_AppKeyNoPrefix();

        // isApplicable returns false when APP_KEY has the base64: prefix
        $this->assertFalse($check->isApplicable($context));
    }

    public function test_not_applicable_when_app_key_missing(): void
    {
        $context = $this->makeContext([]);
        $check   = new S004_AppKeyNoPrefix();

        $this->assertFalse($check->isApplicable($context));
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
