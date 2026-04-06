<?php

declare(strict_types=1);

namespace VaultCheck\Commands;

use VaultCheck\Checks\Codebase\C001_UnusedEnvVar;
use VaultCheck\Checks\Codebase\C002_ReferencedNotDefined;
use VaultCheck\Checks\Codebase\C003_NoDefaultValue;
use VaultCheck\Checks\Codebase\C004_EnvOutsideConfig;
use VaultCheck\Checks\Codebase\C005_CaseInconsistency;
use VaultCheck\Checks\Consistency\X001_SharedDbPassword;
use VaultCheck\Checks\Consistency\X002_SharedAppKey;
use VaultCheck\Checks\Consistency\X003_SecretOverlap;
use VaultCheck\Checks\Consistency\X004_EnvLabelMismatch;
use VaultCheck\Checks\Consistency\X005_UndocumentedStagingConfig;
use VaultCheck\Checks\Environment\E001_EnvFileMissing;
use VaultCheck\Checks\Environment\E002_ExampleMissing;
use VaultCheck\Checks\Environment\E003_ExampleDrift;
use VaultCheck\Checks\Environment\E004_OrphanedKeys;
use VaultCheck\Checks\Environment\E005_EmptyValuesProduction;
use VaultCheck\Checks\Environment\E006_PlaceholderValues;
use VaultCheck\Checks\Environment\E007_AppKeyMissing;
use VaultCheck\Checks\Environment\E008_AppDebugProduction;
use VaultCheck\Checks\Environment\E009_LocalhostDbProduction;
use VaultCheck\Checks\Environment\E010_WindowsLineEndings;
use VaultCheck\Checks\Environment\E011_DuplicateKeys;
use VaultCheck\Checks\Environment\E012_SecretsInExample;
use VaultCheck\Checks\Environment\E013_LogLevelNotSet;
use VaultCheck\Checks\Environment\E014_FileDrivers;
use VaultCheck\Checks\Environment\E015_BackupFiles;
use VaultCheck\Checks\Git\G001_EnvCommittedToGit;
use VaultCheck\Checks\Git\G002_SecretPatternFound;
use VaultCheck\Checks\Git\G003_HighEntropyInHistory;
use VaultCheck\Checks\Git\G004_EnvBackupCommitted;
use VaultCheck\Checks\Git\G005_SecretsInConfigDir;
use VaultCheck\Checks\Git\G006_EnvMissingFromGitignore;
use VaultCheck\Checks\Git\G007_EnvCommittedBeforeIgnore;
use VaultCheck\Checks\Git\G008_UnrotatedLeak;
use VaultCheck\Checks\Permissions\P001_WorldReadable;
use VaultCheck\Checks\Permissions\P002_WorldWritable;
use VaultCheck\Checks\Permissions\P003_PermissionOwnership;
use VaultCheck\Checks\Permissions\P004_EnvInPublicDir;
use VaultCheck\Checks\Strength\S001_SecretTooShort;
use VaultCheck\Checks\Strength\S002_OnlyLowercase;
use VaultCheck\Checks\Strength\S003_KnownWeakValues;
use VaultCheck\Checks\Strength\S004_AppKeyNoPrefix;
use VaultCheck\Checks\Strength\S005_JwtSecretTooShort;
use VaultCheck\Checks\Strength\S006_PasswordEqualsUsername;
use VaultCheck\Engine\CheckEngine;
use VaultCheck\Engine\ScanContext;
use VaultCheck\Parsers\EnvFileParser;
use VaultCheck\Patterns\PatternRegistry;
use VaultCheck\Scanners\CodebaseScanner;
use VaultCheck\Scanners\EntropyScanner;
use VaultCheck\Scanners\GitScanner;

/**
 * Shared context-building and engine-building logic used by all commands.
 */
trait BuildsContext
{
    protected function buildContext(
        string $projectPath,
        bool   $skipHistory = true,
        bool   $fullHistory = false,
    ): ScanContext {
        $parser = new EnvFileParser();

        $envVars       = $parser->parse($projectPath . '/.env');
        $exampleVars   = $parser->parse($projectPath . '/.env.example');
        $envFiles      = $this->discoverEnvFiles($projectPath, $parser);
        $isProduction  = $this->detectProduction($envVars);
        $codebaseRefs  = (new CodebaseScanner())->scan($projectPath);
        $gitScanResult = (new GitScanner(new EntropyScanner(), new PatternRegistry()))
            ->scan($projectPath, $skipHistory, $fullHistory, $envVars);

        return new ScanContext(
            projectPath:   $projectPath,
            envVars:       $envVars,
            exampleVars:   $exampleVars,
            envFiles:      $envFiles,
            multiEnvVars:  $envFiles,
            isProduction:  $isProduction,
            codebaseRefs:  $codebaseRefs,
            gitScanResult: $gitScanResult,
            skipHistory:   $skipHistory,
            fullHistory:   $fullHistory,
        );
    }

    protected function buildEngine(): CheckEngine
    {
        $parser = new EnvFileParser();
        $engine = new CheckEngine();
        $engine->registerMany([
            // Environment hygiene (E001–E015)
            new E001_EnvFileMissing(),
            new E002_ExampleMissing(),
            new E003_ExampleDrift(),
            new E004_OrphanedKeys(),
            new E005_EmptyValuesProduction(),
            new E006_PlaceholderValues(),
            new E007_AppKeyMissing(),
            new E008_AppDebugProduction(),
            new E009_LocalhostDbProduction(),
            new E010_WindowsLineEndings($parser),
            new E011_DuplicateKeys($parser),
            new E012_SecretsInExample(),
            new E013_LogLevelNotSet(),
            new E014_FileDrivers(),
            new E015_BackupFiles(),
            // Codebase (C001–C005)
            new C001_UnusedEnvVar(),
            new C002_ReferencedNotDefined(),
            new C003_NoDefaultValue(),
            new C004_EnvOutsideConfig(),
            new C005_CaseInconsistency(),
            // Permissions (P001–P004)
            new P001_WorldReadable(),
            new P002_WorldWritable(),
            new P003_PermissionOwnership(),
            new P004_EnvInPublicDir(),
            // Consistency (X001–X005)
            new X001_SharedDbPassword(),
            new X002_SharedAppKey(),
            new X003_SecretOverlap(),
            new X004_EnvLabelMismatch(),
            new X005_UndocumentedStagingConfig(),
            // Strength (S001–S006)
            new S001_SecretTooShort(),
            new S002_OnlyLowercase(),
            new S003_KnownWeakValues(),
            new S004_AppKeyNoPrefix(),
            new S005_JwtSecretTooShort(),
            new S006_PasswordEqualsUsername(),
            // Git history (G001–G008)
            new G001_EnvCommittedToGit(),
            new G002_SecretPatternFound(),
            new G003_HighEntropyInHistory(),
            new G004_EnvBackupCommitted(),
            new G005_SecretsInConfigDir(),
            new G006_EnvMissingFromGitignore(),
            new G007_EnvCommittedBeforeIgnore(),
            new G008_UnrotatedLeak(),
        ]);
        return $engine;
    }

    protected function discoverEnvFiles(string $projectPath, EnvFileParser $parser): array
    {
        $files  = glob($projectPath . '/.env*') ?: [];
        $result = [];

        foreach ($files as $filePath) {
            if (is_file($filePath)) {
                $name          = basename($filePath);
                $result[$name] = $parser->parse($filePath);
            }
        }

        return $result;
    }

    protected function detectProduction(array $envVars): bool
    {
        $env = strtolower(trim($envVars['APP_ENV'] ?? ''));
        return in_array($env, ['production', 'prod', 'live'], true);
    }
}
