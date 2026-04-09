<?php

declare(strict_types=1);

namespace VaultCheck\Engine;

/**
 * Holds all results from the GitScanner for a single audit run.
 * Passed into every G* check via ScanContext so git commands run only once.
 */
class GitScanResult
{
    /** Whether a .git directory was found at the project root */
    public bool $gitPresent = false;

    /** Whether .env (or .env.*) was ever committed to git history — G001 */
    public bool $envEverCommitted = false;

    /** Whether .env.bak / .env.backup was ever committed — G004 */
    public bool $backupEverCommitted = false;

    /** Whether .env is currently listed in .gitignore — G006 */
    public bool $envInGitignore = false;

    /** Whether .env was committed before .gitignore was first added — G007 */
    public bool $envCommittedBeforeIgnore = false;

    /**
     * Pattern matches found in git history — G002.
     * Each entry: ['pattern' => string, 'service' => string, 'redacted' => string, 'commit' => string, 'file' => string]
     */
    public array $patternMatches = [];

    /**
     * High-entropy tokens found in git history — G003.
     * Each entry: ['redacted' => string, 'entropy' => float, 'commit' => string, 'file' => string]
     */
    public array $entropyMatches = [];

    /**
     * Credential-looking values found in config/ directory history — G005.
     * Each entry: ['key' => string, 'redacted' => string, 'commit' => string, 'file' => string]
     */
    public array $configCredentials = [];

    /**
     * Keys whose current .env value was also found in git history — G008.
     * Shape: [ 'ENV_KEY' => true ]
     */
    public array $currentSecretsInHistory = [];

    /** Total number of commits examined during the history scan */
    public int $commitsScanned = 0;
}
