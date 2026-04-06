<?php

declare(strict_types=1);

namespace VaultCheck\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use VaultCheck\Parsers\EnvFileParser;
use VaultCheck\Reporters\CliReporter;
use VaultCheck\Reporters\JsonReporter;
use VaultCheck\Reporters\MarkdownReporter;

#[AsCommand(name: 'audit', description: 'Audit environment variable and secret hygiene')]
class AuditCommand extends Command
{
    use BuildsContext;

    protected function configure(): void
    {
        $this
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to the project root to audit',
                (string) getcwd(),
            )
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output format: cli, json, markdown', 'cli')
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Exit with code 1 if any MEDIUM or higher finding exists')
            ->addOption('skip-history', null, InputOption::VALUE_NONE, 'Skip git history scanning')
            ->addOption('full-history', null, InputOption::VALUE_NONE, 'Scan entire git history (default: last 500 commits)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectPath  = rtrim((string) $input->getArgument('path'), '/');
        $outputFormat = strtolower((string) $input->getOption('output'));
        $strict       = (bool) $input->getOption('strict');
        $skipHistory  = (bool) $input->getOption('skip-history');
        $fullHistory  = (bool) $input->getOption('full-history');

        if (!is_dir($projectPath)) {
            $output->writeln("<error>Path does not exist: {$projectPath}</error>");
            return Command::FAILURE;
        }

        $context  = $this->buildContext($projectPath, $skipHistory, $fullHistory);
        $parser   = new EnvFileParser();
        $engine   = $this->buildEngine();
        $findings = $engine->run($context);

        match ($outputFormat) {
            'json'     => (new JsonReporter())->report($findings, $output, $projectPath),
            'markdown' => (new MarkdownReporter())->report($findings, $output, $projectPath),
            default    => (new CliReporter())->report($findings, $output),
        };

        if ($strict && $findings->hasMediumOrAbove()) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
