<?php

declare(strict_types=1);

namespace VaultCheck\Reporters;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;

class CliReporter
{
    private const SEVERITY_COLORS = [
        Finding::SEVERITY_CRITICAL => 'red',
        Finding::SEVERITY_HIGH     => 'yellow',
        Finding::SEVERITY_MEDIUM   => 'cyan',
        Finding::SEVERITY_LOW      => 'white',
        Finding::SEVERITY_INFO     => 'gray',
    ];

    public function report(FindingCollection $findings, OutputInterface $output): void
    {
        $sorted = $findings->sortBySeverity();

        if ($sorted->isEmpty()) {
            $output->writeln('');
            $output->writeln('<fg=green;options=bold> ✓ No findings. Your environment looks clean.</> ');
            $output->writeln('');
            return;
        }

        $output->writeln('');
        $output->writeln(sprintf('<options=bold> VaultCheck — %d finding(s) </>', count($sorted)));
        $output->writeln('');

        foreach ($sorted as $finding) {
            $this->renderFinding($finding, $output);
        }

        $this->renderSummary($sorted, $output);
    }

    private function renderFinding(Finding $finding, OutputInterface $output): void
    {
        $color    = self::SEVERITY_COLORS[$finding->severity] ?? 'white';
        $severity = str_pad($finding->severity, 8);
        $file     = $finding->file ? ' (' . basename($finding->file) . ($finding->line ? ":{$finding->line}" : '') . ')' : '';

        $output->writeln(
            sprintf(
                '  <fg=%s;options=bold>[%s]</> <options=bold>%s</>%s',
                $color,
                $severity,
                $finding->checkId,
                $file,
            )
        );
        $output->writeln(sprintf('    %s', $finding->message));

        if ($finding->suggestion !== '') {
            $output->writeln(sprintf('    <fg=gray>→ %s</>', $finding->suggestion));
        }

        $output->writeln('');
    }

    private function renderSummary(FindingCollection $findings, OutputInterface $output): void
    {
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

        $output->writeln('<options=bold>Summary:</>');
        foreach ($counts as $severity => $count) {
            if ($count === 0) {
                continue;
            }
            $color = self::SEVERITY_COLORS[$severity] ?? 'white';
            $output->writeln(sprintf(
                '  <fg=%s>%s</>: %d',
                $color,
                str_pad($severity, 8),
                $count,
            ));
        }
        $output->writeln('');
    }
}
