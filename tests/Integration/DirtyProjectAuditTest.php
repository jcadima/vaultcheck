<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use VaultCheck\Commands\AuditCommand;

/**
 * Integration test: run the full audit command against the dirty-project fixture.
 *
 * dirty-project is intentionally misconfigured to trigger 41 findings across all
 * categories (E, C, P, X, S). With --skip-history, git checks don't run.
 */
class DirtyProjectAuditTest extends TestCase
{
    private string $fixturePath;

    protected function setUp(): void
    {
        $this->fixturePath = dirname(__DIR__) . '/fixtures/dirty-project';
    }

    public function test_audit_produces_41_findings_on_dirty_project(): void
    {
        $output = $this->runAudit($this->fixturePath, ['--skip-history' => true]);

        $this->assertSame(
            41,
            $output['total'],
            'dirty-project should produce exactly 41 findings with --skip-history. ' .
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
        $this->assertSame(6,  $output['by_severity']['HIGH'],     'Expected 6 HIGH findings');
        $this->assertSame(18, $output['by_severity']['MEDIUM'],   'Expected 18 MEDIUM findings');
        $this->assertSame(15, $output['by_severity']['LOW'],      'Expected 15 LOW findings');
    }

    public function test_min_severity_high_hides_medium_and_low(): void
    {
        $output   = $this->runAudit($this->fixturePath, ['--skip-history' => true, '--min-severity' => 'HIGH']);
        $findings = $output['findings'];

        foreach ($findings as $finding) {
            $this->assertNotContains($finding['severity'], ['MEDIUM', 'LOW', 'INFO'],
                "Finding {$finding['check_id']} with severity {$finding['severity']} should be hidden by --min-severity=HIGH");
        }
        $this->assertGreaterThan(0, count($findings), 'Should still have CRITICAL/HIGH findings');
    }

    public function test_min_severity_invalid_value_is_rejected(): void
    {
        $app = new Application();
        $app->add(new AuditCommand());
        $command = $app->find('audit');
        $tester  = new CommandTester($command);

        $exitCode = $tester->execute([
            'path'           => $this->fixturePath,
            '--min-severity' => 'BOGUS',
            '--output'       => 'json',
        ]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Invalid --min-severity', $tester->getDisplay());
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
