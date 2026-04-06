<?php

declare(strict_types=1);

namespace VaultCheck\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * `vaultcheck snapshot` — saves the current audit state as a baseline.
 *
 * Writes .vaultcheck/snapshot.json in the project root. Future runs of
 * `vaultcheck drift` compare the current state against this baseline to show
 * what has changed (new keys, removed keys, value changes, new/resolved findings).
 *
 * Secret values are never stored raw — only SHA-256 hashes.
 */
#[AsCommand(name: 'snapshot', description: 'Save current audit state as a baseline for drift detection')]
class SnapshotCommand extends Command
{
    use BuildsContext;

    protected function configure(): void
    {
        $this
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to the project root',
                (string) getcwd(),
            )
            ->addOption(
                'include-history',
                null,
                InputOption::VALUE_NONE,
                'Include git history checks in the snapshot (slower)',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectPath     = rtrim((string) $input->getArgument('path'), '/');
        $includeHistory  = (bool) $input->getOption('include-history');

        if (!is_dir($projectPath)) {
            $output->writeln("<error>Path does not exist: {$projectPath}</error>");
            return Command::FAILURE;
        }

        $context  = $this->buildContext($projectPath, skipHistory: !$includeHistory);
        $engine   = $this->buildEngine();
        $findings = $engine->run($context);

        // Build snapshot data
        $envKeys     = [];
        foreach ($context->envVars as $key => $value) {
            $envKeys[$key] = hash('sha256', (string) $value);
        }

        $exampleKeys = array_keys($context->exampleVars);

        $findingsData = [];
        foreach ($findings as $finding) {
            $findingsData[] = [
                'check_id'   => $finding->checkId,
                'severity'   => $finding->severity,
                'message'    => $finding->message,
                'suggestion' => $finding->suggestion,
            ];
        }

        $snapshot = [
            'created_at'   => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'project'      => $projectPath,
            'env_keys'     => $envKeys,
            'example_keys' => $exampleKeys,
            'findings'     => $findingsData,
        ];

        // Write snapshot
        $snapshotDir  = $projectPath . '/.vaultcheck';
        $snapshotPath = $snapshotDir . '/snapshot.json';

        if (!is_dir($snapshotDir) && !mkdir($snapshotDir, 0700, true)) {
            $output->writeln("<error>Could not create directory: {$snapshotDir}</error>");
            return Command::FAILURE;
        }

        if (file_put_contents($snapshotPath, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
            $output->writeln("<error>Could not write snapshot: {$snapshotPath}</error>");
            return Command::FAILURE;
        }

        $keyCount     = count($envKeys);
        $findingCount = count($findingsData);
        $timestamp    = $snapshot['created_at'];

        $output->writeln("<info>Snapshot saved — {$keyCount} key(s), {$findingCount} finding(s) at {$timestamp}</info>");
        $output->writeln("  Written to: {$snapshotPath}");

        if (!$this->isVaultcheckIgnored($projectPath)) {
            $output->writeln('');
            $output->writeln('<comment>Tip: Add .vaultcheck/ to your .gitignore to avoid committing snapshot data.</comment>');
        }

        return Command::SUCCESS;
    }

    private function isVaultcheckIgnored(string $projectPath): bool
    {
        $gitignorePath = $projectPath . '/.gitignore';
        if (!is_file($gitignorePath)) {
            return false;
        }
        $content = file_get_contents($gitignorePath) ?: '';
        return str_contains($content, '.vaultcheck');
    }
}
