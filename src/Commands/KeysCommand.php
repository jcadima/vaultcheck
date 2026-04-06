<?php

declare(strict_types=1);

namespace VaultCheck\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use VaultCheck\Parsers\EnvFileParser;
use VaultCheck\Scanners\CodebaseScanner;

#[AsCommand(name: 'keys', description: 'List all environment keys with their status')]
class KeysCommand extends Command
{
    private const STATUS_DEFINED         = 'DEFINED';
    private const STATUS_EMPTY           = 'EMPTY';
    private const STATUS_EXAMPLE_ONLY    = 'EXAMPLE_ONLY';
    private const STATUS_UNUSED          = 'UNUSED';
    private const STATUS_MISSING_DEFAULT = 'MISSING_DEFAULT';

    private const STATUS_COLORS = [
        self::STATUS_DEFINED         => 'green',
        self::STATUS_EMPTY           => 'yellow',
        self::STATUS_EXAMPLE_ONLY    => 'cyan',
        self::STATUS_UNUSED          => 'gray',
        self::STATUS_MISSING_DEFAULT => 'red',
    ];

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
        $projectPath = rtrim((string) $input->getArgument('path'), '/');

        if (!is_dir($projectPath)) {
            $output->writeln("<error>Path does not exist: {$projectPath}</error>");
            return Command::FAILURE;
        }

        $parser      = new EnvFileParser();
        $envVars     = $parser->parse($projectPath . '/.env');
        $exampleVars = $parser->parse($projectPath . '/.env.example');
        $scanner     = new CodebaseScanner();
        $refs        = $scanner->scan($projectPath);

        // Collect all keys from both sources
        $allKeys = array_unique(array_merge(
            array_keys($envVars),
            array_keys($exampleVars),
        ));
        sort($allKeys);

        $output->writeln('');
        $output->writeln(sprintf('<options=bold> VaultCheck Keys — %s </>', $projectPath));
        $output->writeln('');

        $table = new Table($output);
        $table->setHeaders(['Key', 'Status', 'Value (masked)', 'References']);

        foreach ($allKeys as $key) {
            $status = $this->resolveStatus($key, $envVars, $exampleVars, $refs);
            $color  = self::STATUS_COLORS[$status] ?? 'white';
            $masked = $this->maskValue($envVars[$key] ?? '');
            $usages = isset($refs[$key]) ? count($refs[$key]) . ' ref(s)' : '—';

            $table->addRow([
                $key,
                sprintf('<fg=%s>%s</>', $color, $status),
                $masked,
                $usages,
            ]);
        }

        $table->render();
        $output->writeln('');

        return Command::SUCCESS;
    }

    private function resolveStatus(string $key, array $envVars, array $exampleVars, array $refs): string
    {
        $inEnv     = array_key_exists($key, $envVars);
        $inExample = array_key_exists($key, $exampleVars);
        $value     = $envVars[$key] ?? '';

        if (!$inEnv && $inExample) {
            return self::STATUS_EXAMPLE_ONLY;
        }

        if ($inEnv && $value === '') {
            return self::STATUS_EMPTY;
        }

        if ($inEnv && !isset($refs[$key])) {
            return self::STATUS_UNUSED;
        }

        if ($inEnv && isset($refs[$key])) {
            // Check if any usage lacks a default
            foreach ($refs[$key] as $usage) {
                if (!$usage['hasDefault']) {
                    return self::STATUS_MISSING_DEFAULT;
                }
            }
        }

        return self::STATUS_DEFINED;
    }

    private function maskValue(string $value): string
    {
        if ($value === '') {
            return '<fg=gray>(empty)</>';
        }

        $len = strlen($value);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        return substr($value, 0, 2) . str_repeat('*', min($len - 4, 8)) . substr($value, -2);
    }
}
