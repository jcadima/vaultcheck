<?php

declare(strict_types=1);

namespace VaultCheck\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use VaultCheck\Fixers\DuplicateKeyFixer;
use VaultCheck\Fixers\LineEndingFixer;
use VaultCheck\Fixers\PermissionFixer;
use VaultCheck\Parsers\EnvFileParser;

/**
 * `vaultcheck fix --safe` — automatically applies safe, low-risk fixes.
 *
 * Safe fixes are limited to changes that are unambiguously correct and reversible:
 *   P001 / P002  → chmod 600 .env  (removes world-read/write)
 *   P003         → chmod 640 .env  (removes group-write)
 *   E010         → strip \r line endings from .env
 *   E011         → remove duplicate key lines (keeps first occurrence)
 *
 * NOT in --safe: E003 (needs placeholder judgment), E015 (destructive delete), G checks.
 */
#[AsCommand(name: 'fix', description: 'Automatically fix safe issues found in the audit')]
class FixCommand extends Command
{
    use BuildsContext;

    /** Check IDs handled by --safe mode and their descriptions */
    private const SAFE_FIXES = [
        'P001' => 'Set .env permissions to 600 (remove world-readable bit)',
        'P002' => 'Set .env permissions to 600 (remove world-writable bit)',
        'P003' => 'Set .env permissions to 640 (remove group-writable bit)',
        'E010' => 'Convert Windows CRLF line endings to Unix LF',
        'E011' => 'Remove duplicate keys from .env (keep first occurrence)',
    ];

    protected function configure(): void
    {
        $this
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to the project root to fix',
                (string) getcwd(),
            )
            ->addOption('safe', null, InputOption::VALUE_NONE, 'Apply safe, low-risk automated fixes (required)')
            ->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirmation prompt')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be fixed without applying changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectPath = rtrim((string) $input->getArgument('path'), '/');
        $safe        = (bool) $input->getOption('safe');
        $yes         = (bool) $input->getOption('yes');
        $dryRun      = (bool) $input->getOption('dry-run');

        if (!is_dir($projectPath)) {
            $output->writeln("<error>Path does not exist: {$projectPath}</error>");
            return Command::FAILURE;
        }

        if (!$safe) {
            $output->writeln('<error>Please specify a fix mode. Currently available: --safe</error>');
            $output->writeln('');
            $output->writeln('  <info>--safe</info>  Applies low-risk fixes: file permissions (P001–P003),');
            $output->writeln('          line endings (E010), duplicate keys (E011).');
            return Command::FAILURE;
        }

        $context  = $this->buildContext($projectPath);
        $engine   = $this->buildEngine();
        $findings = $engine->run($context);

        // Filter to only findings we can fix
        $fixable = [];
        foreach ($findings as $finding) {
            if (array_key_exists($finding->checkId, self::SAFE_FIXES)) {
                $fixable[] = $finding;
            }
        }

        if (empty($fixable)) {
            $output->writeln('<info>Nothing to fix — no safe-fixable issues found.</info>');
            return Command::SUCCESS;
        }

        // Show what will be (or would be) fixed
        $label = $dryRun ? 'Would fix' : 'Will fix';
        $output->writeln("<comment>{$label} " . count($fixable) . " issue(s):</comment>");
        $output->writeln('');

        foreach ($fixable as $f) {
            $desc = self::SAFE_FIXES[$f->checkId];
            $output->writeln("  [{$f->checkId}] {$f->message}");
            $output->writeln("       → {$desc}");
        }
        $output->writeln('');

        if ($dryRun) {
            $output->writeln('<comment>Dry run — no changes applied.</comment>');
            return Command::SUCCESS;
        }

        // Confirmation prompt unless --yes
        if (!$yes) {
            /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
            $helper   = $this->getHelper('question');
            $question = new \Symfony\Component\Console\Question\ConfirmationQuestion(
                'Apply these fixes? [y/N] ',
                false,
            );
            if (!$helper->ask($input, $output, $question)) {
                $output->writeln('Aborted.');
                return Command::SUCCESS;
            }
        }

        // Apply fixes
        $parser          = new EnvFileParser();
        $permFixer       = new PermissionFixer();
        $lineEndingFixer = new LineEndingFixer();
        $dupKeyFixer     = new DuplicateKeyFixer();

        $envPath    = $context->envFilePath();
        $fixed      = 0;
        $failed     = 0;
        $p001Fixed  = false;
        $p002Fixed  = false;

        foreach ($fixable as $f) {
            $ok = match ($f->checkId) {
                'P001' => $this->applyPermFix($permFixer, $envPath, $output, $p001Fixed, $p002Fixed),
                'P002' => $this->applyPermFix($permFixer, $envPath, $output, $p001Fixed, $p002Fixed),
                'P003' => $this->applyGroupFix($permFixer, $envPath, $output),
                'E010' => $this->applyLineEndingFix($lineEndingFixer, $envPath, $output),
                'E011' => $this->applyDupKeyFix($dupKeyFixer, $envPath, $parser, $output),
                default => false,
            };

            $ok ? $fixed++ : $failed++;
        }

        $output->writeln('');
        $output->writeln("<info>Done — {$fixed} fix(es) applied" . ($failed > 0 ? ", {$failed} failed" : '') . '.</info>');

        if ($failed > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function applyPermFix(
        PermissionFixer $fixer,
        string          $envPath,
        OutputInterface $output,
        bool            &$p001Fixed,
        bool            &$p002Fixed,
    ): bool {
        // P001 and P002 both apply the same chmod 0600 — only do it once
        if ($p001Fixed || $p002Fixed) {
            $output->writeln('  <info>✓</info> Permissions already set to 600');
            return true;
        }
        $ok = $fixer->fixStrict($envPath);
        if ($ok) {
            $p001Fixed = $p002Fixed = true;
            $output->writeln("  <info>✓</info> chmod 600 {$envPath}");
        } else {
            $output->writeln("  <error>✗</error> Failed to chmod {$envPath}");
        }
        return $ok;
    }

    private function applyGroupFix(PermissionFixer $fixer, string $envPath, OutputInterface $output): bool
    {
        $ok = $fixer->fixGroupWrite($envPath);
        if ($ok) {
            $output->writeln("  <info>✓</info> chmod 640 {$envPath}");
        } else {
            $output->writeln("  <error>✗</error> Failed to chmod {$envPath}");
        }
        return $ok;
    }

    private function applyLineEndingFix(LineEndingFixer $fixer, string $envPath, OutputInterface $output): bool
    {
        $ok = $fixer->fix($envPath);
        if ($ok) {
            $output->writeln("  <info>✓</info> Converted CRLF → LF in {$envPath}");
        } else {
            $output->writeln("  <error>✗</error> Failed to fix line endings in {$envPath}");
        }
        return $ok;
    }

    private function applyDupKeyFix(
        DuplicateKeyFixer $fixer,
        string            $envPath,
        EnvFileParser     $parser,
        OutputInterface   $output,
    ): bool {
        $ok = $fixer->fix($envPath, $parser);
        if ($ok) {
            $output->writeln("  <info>✓</info> Removed duplicate keys from {$envPath}");
        } else {
            $output->writeln("  <error>✗</error> Failed to remove duplicate keys from {$envPath}");
        }
        return $ok;
    }
}
