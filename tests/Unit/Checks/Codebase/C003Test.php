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

    public function test_clean_when_key_is_defined_in_env(): void
    {
        // C003 should not fire if the key is already defined in .env — it won't be null at runtime
        $context = new ScanContext(
            projectPath:  '/tmp/fake',
            envVars:      ['STRIPE_KEY' => 'sk_live_real_value'],
            exampleVars:  [],
            envFiles:     [],
            multiEnvVars: [],
            isProduction: false,
            codebaseRefs: [
                'STRIPE_KEY' => [['file' => 'config/services.php', 'line' => 5, 'hasDefault' => false]],
            ],
        );
        $results = (new C003_NoDefaultValue())->run($context);

        $this->assertTrue($results->isEmpty(), 'C003 should not fire when key is already in .env');
    }

    public function test_silent_when_all_usages_are_in_config_files(): void
    {
        $context = $this->makeContext([
            'MEMCACHED_HOST'    => [['file' => 'config/cache.php',   'line' => 30, 'hasDefault' => false]],
            'DYNAMODB_TABLE'    => [['file' => 'config/session.php', 'line' => 14, 'hasDefault' => false]],
        ]);
        $results = (new C003_NoDefaultValue())->run($context);

        $this->assertTrue($results->isEmpty(), 'C003 should not fire for config-only usages of optional framework vars');
    }

    public function test_silent_with_absolute_config_path(): void
    {
        // Real-world paths from CodebaseScanner use absolute paths via getRealPath()
        $context = $this->makeContext([
            'MEMCACHED_HOST' => [['file' => '/var/www/html/config/cache.php', 'line' => 30, 'hasDefault' => false]],
        ]);
        $results = (new C003_NoDefaultValue())->run($context);

        $this->assertTrue($results->isEmpty(), 'C003 should suppress absolute-path config-only usages');
    }

    public function test_fires_when_app_code_lacks_default(): void
    {
        $context = $this->makeContext([
            'PAYMENT_KEY' => [['file' => 'app/Billing/PaymentService.php', 'line' => 8, 'hasDefault' => false]],
        ]);
        $results = (new C003_NoDefaultValue())->run($context);

        $this->assertSame(1, $results->count());
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
