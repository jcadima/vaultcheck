<?php

declare(strict_types=1);

namespace VaultCheck\Scanners;

use Symfony\Component\Process\Process;
use VaultCheck\Engine\GitScanResult;
use VaultCheck\Patterns\PatternRegistry;

/**
 * Scans a project's git history for committed secrets.
 *
 * All git work is done here and stored in GitScanResult so
 * individual checks (G001–G008) never shell out themselves.
 */
class GitScanner
{
    public function __construct(
        private readonly EntropyScanner  $entropyScanner,
        private readonly PatternRegistry $patternRegistry,
    ) {}

    /**
     * Run the full git scan and return a populated GitScanResult.
     *
     * @param string $projectPath  Absolute path to the project root
     * @param bool   $skipHistory  If true, skip commit scanning (only check .gitignore)
     * @param bool   $fullHistory  If true, scan all commits; otherwise last 500
     * @param array  $currentEnv   Current .env key=>value pairs (for G008 comparison)
     */
    public function scan(
        string $projectPath,
        bool   $skipHistory,
        bool   $fullHistory,
        array  $currentEnv,
    ): GitScanResult {
        $result = new GitScanResult();

        // Does .git directory exist?
        $result->gitPresent = is_dir($projectPath . '/.git');

        // G006: check .gitignore regardless of git history
        $result->envInGitignore = $this->checkEnvInGitignore($projectPath);

        if (!$result->gitPresent || $skipHistory) {
            return $result;
        }

        $commitLimit = $fullHistory ? [] : ['--max-count=500'];

        // Collect all commit hashes that touched .env* or config/* files
        $envCommits    = $this->getCommitsTouchingPaths($projectPath, $commitLimit, ['.env', '.env.*', '.env.bak', '.env.backup', '.env.old']);
        $configCommits = $this->getCommitsTouchingPaths($projectPath, $commitLimit, ['config/']);

        // Flat array of all added lines: [{commit, file, line}]
        $allAddedLines = [];

        // Process .env* commits
        foreach ($envCommits as [$hash, $file]) {
            if (str_starts_with($file, '.env')) {
                $result->envEverCommitted = true;
            }
            if (preg_match('/\.env\.(bak|backup|old)$/i', $file)) {
                $result->backupEverCommitted = true;
            }
        }

        // Get added lines from all .env* commits
        $processedHashes = [];
        foreach ($envCommits as [$hash, $file]) {
            if (isset($processedHashes[$hash])) {
                continue;
            }
            $processedHashes[$hash] = true;
            $lines = $this->getAddedLinesFromCommit($projectPath, $hash);
            foreach ($lines as $lineData) {
                $allAddedLines[] = $lineData;
            }
        }

        // G005: config credentials — get added lines from config commits
        $configHashes = [];
        foreach ($configCommits as [$hash, $file]) {
            if (isset($configHashes[$hash])) {
                continue;
            }
            $configHashes[$hash] = true;
            $lines = $this->getAddedLinesFromCommit($projectPath, $hash);
            foreach ($lines as $lineData) {
                // Run pattern scan on config lines
                $matches = $this->patternRegistry->scan($lineData['line'], $lineData['commit'], $lineData['file']);
                foreach ($matches as $match) {
                    // Extract the key name from the line if possible (KEY=value format)
                    $key = $this->extractKeyName($lineData['line']);
                    $result->configCredentials[] = [
                        'key'      => $key,
                        'redacted' => $match['redacted'],
                        'commit'   => $lineData['commit'],
                        'file'     => $lineData['file'],
                    ];
                }
            }
        }

        // G002 + G003: run pattern and entropy scans on all .env* added lines
        foreach ($allAddedLines as $lineData) {
            $line   = $lineData['line'];
            $commit = $lineData['commit'];
            $file   = $lineData['file'];

            // G002: pattern matching
            $matches = $this->patternRegistry->scan($line, $commit, $file);
            foreach ($matches as $match) {
                $result->patternMatches[] = $match;
            }

            // G003: entropy scanning
            $tokens = $this->entropyScanner->extractTokens($line);
            foreach ($tokens as $token) {
                if ($this->entropyScanner->isKnownSafe($token)) {
                    continue;
                }
                $entropy = $this->entropyScanner->calculate($token);
                if ($entropy >= EntropyScanner::ENTROPY_THRESHOLD) {
                    $result->entropyMatches[] = [
                        'redacted' => $this->entropyScanner->redact($token),
                        'entropy'  => round($entropy, 2),
                        'commit'   => $commit,
                        'file'     => $file,
                    ];
                }
            }
        }

        // G007: did .env get committed BEFORE .gitignore was first added?
        $result->envCommittedBeforeIgnore = $this->checkEnvCommittedBeforeIgnore($projectPath);

        // G008: does any current .env value appear in git history?
        $allHistoricalText = implode("\n", array_column($allAddedLines, 'line'));
        foreach ($currentEnv as $key => $value) {
            if (strlen((string) $value) < 8) {
                continue; // too short — too many false positives
            }
            if (str_contains($allHistoricalText, (string) $value)) {
                $result->currentSecretsInHistory[$key] = true;
            }
        }

        return $result;
    }

    /**
     * Returns true if .env is covered by a .gitignore pattern.
     */
    private function checkEnvInGitignore(string $projectPath): bool
    {
        $gitignorePath = $projectPath . '/.gitignore';
        if (!file_exists($gitignorePath)) {
            return false;
        }

        $lines = file($gitignorePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#')) {
                continue;
            }
            // Matches ".env", ".env*", "/.env", "*.env", etc.
            if (preg_match('/^\/?(\.env[\*\.]?|\.env$)/', $line)) {
                return true;
            }
            if ($line === '.env' || $line === '/.env' || $line === '.env*') {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns [[hash, file], ...] for commits that added files matching given patterns.
     *
     * @param string[]  $commitLimit  e.g. ['--max-count=500'] or []
     * @param string[]  $pathPatterns e.g. ['.env', '.env.*']
     * @return array<int, array{0: string, 1: string}>
     */
    private function getCommitsTouchingPaths(string $projectPath, array $commitLimit, array $pathPatterns): array
    {
        $args = array_merge(
            ['git', 'log', '--all', '--format=%H', '--name-only', '--diff-filter=A'],
            $commitLimit,
            ['--'],
            $pathPatterns,
        );

        $process = new Process($args, $projectPath, timeout: 60);
        $process->run();

        if (!$process->isSuccessful()) {
            return [];
        }

        $output = trim($process->getOutput());
        if ($output === '') {
            return [];
        }

        $results = [];
        $currentHash = '';

        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            // Git commit hashes are 40 hex chars
            if (preg_match('/^[0-9a-f]{40}$/', $line)) {
                $currentHash = $line;
            } elseif ($currentHash !== '') {
                $results[] = [$currentHash, $line];
            }
        }

        return $results;
    }

    /**
     * Get all added lines (+) from a commit's diff output.
     *
     * @return array<int, array{commit: string, file: string, line: string}>
     */
    private function getAddedLinesFromCommit(string $projectPath, string $hash): array
    {
        $process = new Process(
            ['git', 'show', '--format=', '--unified=0', $hash],
            $projectPath,
            timeout: 30,
        );
        $process->run();

        if (!$process->isSuccessful()) {
            return [];
        }

        $lines = [];
        $currentFile = '';

        foreach (explode("\n", $process->getOutput()) as $line) {
            // Detect file name from diff header
            if (str_starts_with($line, '+++ b/')) {
                $currentFile = substr($line, 6);
                continue;
            }
            // Skip diff headers
            if (str_starts_with($line, '---') || str_starts_with($line, 'diff ') ||
                str_starts_with($line, 'index ') || str_starts_with($line, '@@')) {
                continue;
            }
            // Added lines start with +
            if (str_starts_with($line, '+')) {
                $content = substr($line, 1); // strip leading +
                if (trim($content) !== '') {
                    $lines[] = [
                        'commit' => $hash,
                        'file'   => $currentFile,
                        'line'   => $content,
                    ];
                }
            }
        }

        return $lines;
    }

    /**
     * Check if .env was committed before .gitignore was first added.
     *
     * Uses graph topology (commit ancestry order) rather than wall-clock timestamps
     * so that commits made in the same second are still ordered correctly.
     */
    private function checkEnvCommittedBeforeIgnore(string $projectPath): bool
    {
        $envHash    = $this->getFirstCommitHash($projectPath, '.env');
        $ignoreHash = $this->getFirstCommitHash($projectPath, '.gitignore');

        if ($envHash === null) {
            // .env was never committed — nothing to report
            return false;
        }

        if ($ignoreHash === null) {
            // .gitignore was never committed — .env predates it
            return true;
        }

        if ($envHash === $ignoreHash) {
            // Same commit added both — not a violation
            return false;
        }

        // If ignoreHash is an ancestor of envHash, ignore came first — OK.
        // If envHash is an ancestor of ignoreHash, env came first — violation.
        $process = new Process(
            ['git', 'merge-base', '--is-ancestor', $ignoreHash, $envHash],
            $projectPath,
            timeout: 10,
        );
        $process->run();

        if ($process->getExitCode() === 0) {
            // ignoreHash is an ancestor of envHash → ignore was committed first → OK
            return false;
        }

        // Check if envHash is ancestor of ignoreHash → env was committed first → violation
        $process2 = new Process(
            ['git', 'merge-base', '--is-ancestor', $envHash, $ignoreHash],
            $projectPath,
            timeout: 10,
        );
        $process2->run();

        return $process2->getExitCode() === 0;
    }

    private function getFirstCommitHash(string $projectPath, string $file): ?string
    {
        // --reverse gives oldest first; first line is the earliest commit that added this file
        $process = new Process(
            ['git', 'log', '--all', '--format=%H', '--diff-filter=A', '--reverse', '--', $file],
            $projectPath,
            timeout: 30,
        );
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        $lines = array_filter(array_map('trim', explode("\n", $process->getOutput())));
        return $lines ? reset($lines) : null;
    }

    /**
     * Extract KEY name from a "KEY=value" line, or return an empty string.
     */
    private function extractKeyName(string $line): string
    {
        if (preg_match('/^([A-Z_][A-Z0-9_]*)\s*=/', ltrim($line), $m)) {
            return $m[1];
        }
        return '';
    }
}
