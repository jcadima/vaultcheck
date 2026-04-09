<?php

declare(strict_types=1);

namespace VaultCheck\Reporters;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\GitScanResult;

class CliReporter
{
    private const SEVERITY_COLORS = [
        Finding::SEVERITY_CRITICAL => 'red',
        Finding::SEVERITY_HIGH     => 'yellow',
        Finding::SEVERITY_MEDIUM   => 'cyan',
        Finding::SEVERITY_LOW      => 'white',
        Finding::SEVERITY_INFO     => 'gray',
    ];

    public function report(
        FindingCollection  $findings,
        OutputInterface    $output,
        ?FindingCollection $allFindings = null,
        ?GitScanResult     $gitScanResult = null,
    ): void {
        $sorted  = $findings->sortBySeverity();
        $hidden  = $allFindings !== null ? count($allFindings) - count($findings) : 0;

        if ($sorted->isEmpty()) {
            $output->writeln('');
            $this->renderGitSummary($gitScanResult, $allFindings ?? $findings, $output);
            if ($hidden > 0) {
                $output->writeln('<fg=green;options=bold> ✓ No CRITICAL or HIGH findings.</> ');
                $output->writeln(sprintf(
                    '   <fg=gray>%d lower-severity finding(s) not shown — run with --min-severity=MEDIUM to review.</>',
                    $hidden,
                ));
            } else {
                $output->writeln('<fg=green;options=bold> ✓ No findings. Your environment looks clean.</> ');
            }
            $output->writeln('');
            return;
        }

        $output->writeln('');
        $output->writeln(sprintf('<options=bold> VaultCheck — %d finding(s) </>', count($sorted)));
        $output->writeln('');
        $this->renderGitSummary($gitScanResult, $allFindings ?? $findings, $output);

        foreach ($sorted as $finding) {
            $this->renderFinding($finding, $output);
        }

        $this->renderSummary($sorted, $output, $hidden, $allFindings);
    }

    private function renderGitSummary(
        ?GitScanResult    $gitScanResult,
        FindingCollection $allFindings,
        OutputInterface   $output,
    ): void {
        if ($gitScanResult === null || $gitScanResult->commitsScanned === 0) {
            return;
        }

        $gitIssues = count(array_filter(
            iterator_to_array($allFindings),
            fn(Finding $f) => str_starts_with($f->checkId, 'G'),
        ));

        $count   = number_format($gitScanResult->commitsScanned);
        $summary = $gitIssues > 0
            ? "{$gitIssues} issue(s) found"
            : 'no issues found';

        $output->writeln(sprintf(' <fg=gray>Git history: %s commits scanned, %s.</>', $count, $summary));
        $output->writeln('');
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

    private function renderSummary(
        FindingCollection  $findings,
        OutputInterface    $output,
        int                $hidden = 0,
        ?FindingCollection $allFindings = null,
    ): void {
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

        if ($hidden > 0 && $allFindings !== null) {
            $allCounts = array_fill_keys(array_keys($counts), 0);
            foreach ($allFindings as $f) {
                $allCounts[$f->severity]++;
            }
            $parts = [];
            foreach ($allCounts as $severity => $total) {
                $diff = $total - ($counts[$severity] ?? 0);
                if ($diff > 0) {
                    $parts[] = "{$severity}: {$diff}";
                }
            }
            $output->writeln(sprintf(
                '  <fg=gray>+ %d finding(s) not shown (%s). Use --min-severity=MEDIUM to see more.</>',
                $hidden,
                implode(', ', $parts),
            ));
        }

        $output->writeln('');
    }
}
