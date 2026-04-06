<?php

declare(strict_types=1);

namespace VaultCheck\Engine;

/**
 * Holds all parsed data for a single audit run.
 * Passed into every check so they share a single load of the filesystem.
 */
class ScanContext
{
    /**
     * @param string   $projectPath     Absolute path to the project root
     * @param array    $envVars         Key=>value from .env (or empty if missing)
     * @param array    $exampleVars     Key=>value from .env.example (or empty)
     * @param array    $envFiles        All discovered .env* file paths
     * @param array    $multiEnvVars    Map of filename => key=>value for multi-env checks
     * @param bool     $isProduction    Whether APP_ENV looks like production
     * @param array          $codebaseRefs    Map of KEY => [{file, line, hasDefault}] from CodebaseScanner
     * @param GitScanResult|null $gitScanResult Results from GitScanner (null if git not present or skipped)
     * @param bool           $skipHistory     Whether to skip git scanning
     * @param bool           $fullHistory     Whether to scan entire git history
     */
    public function __construct(
        public readonly string          $projectPath,
        public readonly array           $envVars,
        public readonly array           $exampleVars,
        public readonly array           $envFiles,
        public readonly array           $multiEnvVars,
        public readonly bool            $isProduction,
        public readonly array           $codebaseRefs = [],
        public readonly ?GitScanResult  $gitScanResult = null,
        public readonly bool            $skipHistory = false,
        public readonly bool            $fullHistory = false,
    ) {}

    public function envFilePath(): string
    {
        return $this->projectPath . '/.env';
    }

    public function exampleFilePath(): string
    {
        return $this->projectPath . '/.env.example';
    }

    public function gitignorePath(): string
    {
        return $this->projectPath . '/.gitignore';
    }
}
