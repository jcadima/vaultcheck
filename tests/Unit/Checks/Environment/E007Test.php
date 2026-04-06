<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Checks\Environment;

use PHPUnit\Framework\TestCase;
use VaultCheck\Checks\Environment\E007_AppKeyMissing;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\ScanContext;

class E007Test extends TestCase
{
    public function test_fires_when_app_key_absent(): void
    {
        $context = $this->makeContext([]);
        $results = (new E007_AppKeyMissing())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame('E007', $findings[0]->checkId);
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
    }

    public function test_fires_when_app_key_empty(): void
    {
        $context = $this->makeContext(['APP_KEY' => '']);
        $results = (new E007_AppKeyMissing())->run($context);

        $this->assertSame(1, $results->count());
    }

    public function test_fires_medium_when_app_key_lacks_base64_prefix(): void
    {
        $context = $this->makeContext(['APP_KEY' => 'some-random-string-without-prefix']);
        $results = (new E007_AppKeyMissing())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame(Finding::SEVERITY_MEDIUM, $findings[0]->severity);
    }

    public function test_fires_when_base64_decoded_key_too_short(): void
    {
        // base64 of a short string (less than 32 bytes)
        $shortKey = base64_encode('too-short');
        $context  = $this->makeContext(['APP_KEY' => 'base64:' . $shortKey]);
        $results  = (new E007_AppKeyMissing())->run($context);

        $this->assertSame(1, $results->count());
        $findings = iterator_to_array($results->getIterator());
        $this->assertSame(Finding::SEVERITY_HIGH, $findings[0]->severity);
    }

    public function test_clean_when_valid_base64_key(): void
    {
        // 32 bytes base64-encoded
        $validKey = base64_encode(str_repeat('x', 32));
        $context  = $this->makeContext(['APP_KEY' => 'base64:' . $validKey]);
        $results  = (new E007_AppKeyMissing())->run($context);

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
