<?php

declare(strict_types=1);

namespace VaultCheck\Reporters;

use Symfony\Component\Console\Output\OutputInterface;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;

class JsonReporter
{
    public function report(FindingCollection $findings, OutputInterface $output, string $projectPath = ''): void
    {
        $sorted = $findings->sortBySeverity();

        $bySeverity = [
            Finding::SEVERITY_CRITICAL => 0,
            Finding::SEVERITY_HIGH     => 0,
            Finding::SEVERITY_MEDIUM   => 0,
            Finding::SEVERITY_LOW      => 0,
            Finding::SEVERITY_INFO     => 0,
        ];

        foreach ($sorted as $finding) {
            $bySeverity[$finding->severity]++;
        }

        $output->writeln(json_encode([
            'generated_at' => date('c'),
            'project'      => $projectPath,
            'total'        => count($sorted),
            'by_severity'  => $bySeverity,
            'findings'     => $sorted->toArray(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
