<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use VaultCheck\Commands\AuditCommand;

/**
 * Integration test: run the full audit command against the dirty-project fixture.
 *
 * dirty-project is intentionally misconfigured to trigger 43 findings across all
 * categories (E, C, P, X, S). With --skip-history, git checks don't run.
 */
class DirtyProjectAuditTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        $this->fixturePath = dirname(__DIR__) . '/fixtures/dirty-project';
    }

    public function test_audit_produces_43_findings_on_dirty_project(): void
    {
        $output = $this->runAudit($this->fixturePath, ['--skip-history' => true]);

        $this->assertSame(
            43,
            $output['total'],
            'dirty-project should produce exactly 43 findings with --skip-history. ' .
            'Found check IDs: ' . implode(', ', array_column($output['findings'], 'check_id'))
        );
    }

    public function test_audit_includes_critical_findings(): void
    {
        $output   = $this->runAudit($this->fixturePath, ['--skip-history' => true]);
        $checkIds = array_column($output['findings'], 'check_id');

        // E008: APP_DEBUG=true in production
        $this->assertContains('E008', $checkIds, 'E008 (APP_DEBUG=true) should fire');
        // P001: .env is world-readable (fixture has 0664 permissions)
        $this->assertContains('P001', $checkIds, 'P001 (world-readable .env) should fire');
    }

    public function test_audit_includes_high_severity_findings(): void
    {
        $output   = $this->runAudit($this->fixturePath, ['--skip-history' => true]);
        $checkIds = array_column($output['findings'], 'check_id');

        // E007: APP_KEY is empty
        $this->assertContains('E007', $checkIds, 'E007 (empty APP_KEY) should fire');
        // C002: code references undefined env var
        $this->assertContains('C002', $checkIds, 'C002 (referenced but undefined) should fire');
    }

    public function test_audit_severity_breakdown(): void
    {
        $output = $this->runAudit($this->fixturePath, ['--skip-history' => true]);

        $this->assertSame(2,  $output['by_severity']['CRITICAL'], 'Expected 2 CRITICAL findings');
        $this->assertSame(7,  $output['by_severity']['HIGH'],     'Expected 7 HIGH findings');
        $this->assertSame(19, $output['by_severity']['MEDIUM'],   'Expected 19 MEDIUM findings');
        $this->assertSame(15, $output['by_severity']['LOW'],      'Expected 15 LOW findings');
    }

    private function runAudit(string $path, array $options = []): array
    {
        $app = new Application();
        $app->add(new AuditCommand());
        $command = $app->find('audit');

        $tester = new CommandTester($command);
        $tester->execute(array_merge([
            'path'     => $path,
            '--output' => 'json',
        ], $options));

        $decoded = json_decode($tester->getDisplay(), true);
        $this->assertNotNull($decoded, 'AuditCommand produced invalid JSON output');
        return $decoded;
    }
}
