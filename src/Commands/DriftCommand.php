<?php

declare(strict_types=1);

namespace VaultCheck\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * `vaultcheck drift` — compares the current audit state against a saved snapshot.
 *
 * Shows:
 * - Keys added/removed from .env since the snapshot
 * - Keys whose value has changed (detected via SHA-256 hash)
 * - New findings that weren't in the snapshot
 * - Findings that have been resolved since the snapshot
 */
#[AsCommand(name: 'drift', description: 'Compare current state to the saved snapshot')]
class DriftCommand extends Command
{
    use BuildsContext;

    protected function configure(): void
    {
        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'Path to the project root',
            (string) getcwd(),
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectPath  = rtrim((string) $input->getArgument('path'), '/');
        $snapshotPath = $projectPath . '/.vaultcheck/snapshot.json';

        if (!is_dir($projectPath)) {
            $output->writeln("<error>Path does not exist: {$projectPath}</error>");
            return Command::FAILURE;
        }

        if (!is_file($snapshotPath)) {
            $output->writeln('<error>No snapshot found. Run `vaultcheck snapshot` first.</error>');
            return Command::FAILURE;
        }

        $snapshotJson = file_get_contents($snapshotPath);
        if ($snapshotJson === false) {
            $output->writeln("<error>Could not read snapshot: {$snapshotPath}</error>");
            return Command::FAILURE;
        }

        $snapshot = json_decode($snapshotJson, true);
        if (!is_array($snapshot)) {
            $output->writeln('<error>Snapshot file is corrupted. Re-run `vaultcheck snapshot` to recreate it.</error>');
            return Command::FAILURE;
        }

        // Build current state
        $context  = $this->buildContext($projectPath);
        $engine   = $this->buildEngine();
        $findings = $engine->run($context);

        $snapshotKeys     = $snapshot['env_keys'] ?? [];
        $snapshotFindings = $snapshot['findings'] ?? [];
        $snapshotAt       = $snapshot['created_at'] ?? 'unknown';

        $output->writeln('');
        $output->writeln("<info>Drift report — snapshot taken at: {$snapshotAt}</info>");
        $output->writeln('');

        // ── Key Changes ──────────────────────────────────────────────────────
        $currentKeys = [];
        foreach ($context->envVars as $key => $value) {
            $currentKeys[$key] = hash('sha256', (string) $value);
        }

        $added   = array_diff_key($currentKeys, $snapshotKeys);
        $removed = array_diff_key($snapshotKeys, $currentKeys);

        $changed = [];
        foreach ($currentKeys as $key => $hash) {
            if (isset($snapshotKeys[$key]) && $snapshotKeys[$key] !== $hash) {
                $changed[$key] = true;
            }
        }

        $same = array_diff_key($currentKeys, $added, $changed);

        $keyChanges = count($added) + count($removed) + count($changed);

        $output->writeln('<comment>Key Changes:</comment>');
        if ($keyChanges === 0 && empty($same)) {
            $output->writeln('  (no .env keys)');
        } else {
            foreach ($added as $key => $_) {
                $output->writeln("  <info>[+] NEW     </info> {$key}  <comment>(added)</comment>");
            }
            foreach ($removed as $key => $_) {
                $output->writeln("  <fg=red>[-] REMOVED</> {$key}  <comment>(removed)</comment>");
            }
            foreach ($changed as $key => $_) {
                $output->writeln("  <fg=yellow>[~] CHANGED</> {$key}  <comment>(value changed)</comment>");
            }
            foreach (array_keys($same) as $key) {
                $output->writeln("  [=] same     {$key}");
            }
            if ($keyChanges === 0) {
                $output->writeln('  (no key changes)');
            }
        }

        $output->writeln('');

        // ── Finding Changes ───────────────────────────────────────────────────
        $currentFindingKeys  = [];
        foreach ($findings as $f) {
            $currentFindingKeys[$f->checkId . '::' . $f->message] = $f;
        }

        $snapshotFindingKeys = [];
        foreach ($snapshotFindings as $sf) {
            $snapshotFindingKeys[$sf['check_id'] . '::' . $sf['message']] = $sf;
        }

        $newFindings      = array_diff_key($currentFindingKeys, $snapshotFindingKeys);
        $resolvedFindings = array_diff_key($snapshotFindingKeys, $currentFindingKeys);

        $output->writeln('<comment>Finding Changes:</comment>');

        if (empty($newFindings) && empty($resolvedFindings)) {
            $output->writeln('  (no finding changes)');
        } else {
            foreach ($newFindings as $f) {
                $sev = $this->colorSeverity($f->severity);
                $output->writeln("  <info>[+] NEW     </info> [{$sev}] {$f->checkId}  {$f->message}");
            }
            foreach ($resolvedFindings as $sf) {
                $sev = $this->colorSeverity($sf['severity']);
                $output->writeln("  <fg=green>[-] RESOLVED</> [{$sev}] {$sf['check_id']}  {$sf['message']}");
            }
        }

        $output->writeln('');

        if ($keyChanges === 0 && empty($newFindings) && empty($resolvedFindings)) {
            $output->writeln('<info>No drift detected since snapshot.</info>');
        } else {
            $nNew      = count($newFindings);
            $nResolved = count($resolvedFindings);
            $output->writeln(
                "<comment>Summary:</comment> {$keyChanges} key change(s), "
                . "{$nNew} new finding(s), {$nResolved} resolved finding(s)"
            );
        }

        return Command::SUCCESS;
    }

    private function colorSeverity(string $severity): string
    {
        return match ($severity) {
            'CRITICAL' => '<fg=red>CRITICAL</>',
            'HIGH'     => '<fg=red>HIGH    </>',
            'MEDIUM'   => '<fg=yellow>MEDIUM  </>',
            'LOW'      => '<fg=blue>LOW     </>',
            default    => $severity,
        };
    }
}
