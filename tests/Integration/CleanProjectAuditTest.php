<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use VaultCheck\Commands\AuditCommand;

/**
 * Integration test: run the full audit command against the clean-project fixture.
 *
 * The clean-project fixture has a well-configured .env. The only expected findings
 * are data-quality ones: S001 (DB_PASSWORD too short) and S003 (DB_PASSWORD is a
 * known-weak value). File-permission findings (P001, P003) may also appear depending
 * on the OS umask, so we test for known findings instead of exact totals.
 */
class CleanProjectAuditTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        $this->fixturePath = dirname(__DIR__) . '/fixtures/clean-project';
    }

    public function test_audit_fires_known_data_findings_on_clean_project(): void
    {
        $output = $this->runAudit($this->fixturePath, ['--skip-history' => true]);

        $checkIds = array_column($output['findings'], 'check_id');

        // S001: DB_PASSWORD=secret123 is 9 chars, below the 16-char minimum
        $this->assertContains('S001', $checkIds, 'S001 should fire for short DB_PASSWORD');
        // S003: secret123 is in the known-weak list
        $this->assertContains('S003', $checkIds, 'S003 should fire for known-weak DB_PASSWORD');
    }

    public function test_audit_has_no_application_logic_critical_findings(): void
    {
        $output   = $this->runAudit($this->fixturePath, ['--skip-history' => true]);
        $checkIds = array_column($output['findings'], 'check_id');

        // E008 (APP_DEBUG in production) should NOT fire on the clean project
        $this->assertNotContains('E008', $checkIds, 'E008 should not fire on clean project');
        // X002 (shared APP_KEY) should NOT fire on the clean project
        $this->assertNotContains('X002', $checkIds, 'X002 should not fire on clean project');
    }

    public function test_audit_json_output_has_expected_structure(): void
    {
        $output = $this->runAudit($this->fixturePath, ['--skip-history' => true]);

        $this->assertArrayHasKey('total',       $output);
        $this->assertArrayHasKey('by_severity', $output);
        $this->assertArrayHasKey('findings',    $output);
        $this->assertIsArray($output['findings']);
    }

    private function runAudit(string $path, array $options = []): array
    {
        $app = new Application();
        $app->add(new AuditCommand());
        $command = $app->find('audit');

        $tester = new CommandTester($command);
        $tester->execute(array_merge([
            'path'           => $path,
            '--output'       => 'json',
            '--min-severity' => 'LOW',   // tests always inspect full output
        ], $options));

        $decoded = json_decode($tester->getDisplay(), true);
        $this->assertNotNull($decoded, 'AuditCommand produced invalid JSON output');
        return $decoded;
    }
}
