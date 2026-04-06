<?php

declare(strict_types=1);

namespace VaultCheck\Reporters;

use Symfony\Component\Console\Output\OutputInterface;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;

class MarkdownReporter
{
    public function report(FindingCollection $findings, OutputInterface $output, string $projectPath = ''): void
    {
        $output->writeln('# VaultCheck Security Audit');
        $output->writeln('');
        $output->writeln(sprintf('**Generated:** %s', date('Y-m-d H:i:s')));
        if ($projectPath !== '') {
            $output->writeln(sprintf('**Project:** `%s`', $projectPath));
        }
        $output->writeln('');

        if ($findings->isEmpty()) {
            $output->writeln('**No findings. Your environment looks clean.**');
            return;
        }

        // Summary table
        $counts = [
            Finding::SEVERITY_CRITICAL => 0,
            Finding::SEVERITY_HIGH     => 0,
            Finding::SEVERITY_MEDIUM   => 0,
            Finding::SEVERITY_LOW      => 0,
            Finding::SEVERITY_INFO     => 0,
        ];
        foreach ($findings as $finding) {
            $counts[$finding->severity]++;
        }

        $output->writeln('## Summary');
        $output->writeln('');
        $output->writeln('| Severity | Count |');
        $output->writeln('|----------|-------|');
        foreach ($counts as $severity => $count) {
            if ($count > 0) {
                $output->writeln(sprintf('| **%s** | %d |', $severity, $count));
            }
        }
        $output->writeln('');
        $output->writeln(sprintf('**Total findings: %d**', count($findings)));
        $output->writeln('');

        // Findings table
        $output->writeln('## Findings');
        $output->writeln('');
        $output->writeln('| Severity | Check | File | Message | Suggestion |');
        $output->writeln('|----------|-------|------|---------|------------|');

        foreach ($findings->sortBySeverity() as $finding) {
            $file = $finding->file
                ? '`' . basename($finding->file) . ($finding->line ? ":{$finding->line}" : '') . '`'
                : '—';

            $output->writeln(sprintf(
                '| **%s** | %s | %s | %s | %s |',
                $finding->severity,
                $finding->checkId,
                $file,
                str_replace('|', '\\|', $finding->message),
                str_replace('|', '\\|', $finding->suggestion),
            ));
        }
    }
}
