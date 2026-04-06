<?php

declare(strict_types=1);

namespace VaultCheck\Fixers;

use VaultCheck\Parsers\EnvFileParser;

/**
 * Fixes E011 (duplicate keys in .env) by removing all but the first occurrence of each key.
 */
class DuplicateKeyFixer
{
    /**
     * Remove duplicate key lines from the .env file, keeping the first occurrence of each key.
     *
     * Strategy:
     * 1. parseWithLineNumbers() to find which line numbers are duplicates (1-based)
     * 2. Read raw lines with file() (preserves line endings)
     * 3. Filter out duplicate lines
     * 4. Rewrite the file
     */
    public function fix(string $filePath, EnvFileParser $parser): bool
    {
        $rows = $parser->parseWithLineNumbers($filePath);

        // Track first-seen line per key; mark later occurrences for removal
        $seen        = [];
        $removeLines = [];

        foreach ($rows as $row) {
            if (isset($seen[$row['key']])) {
                $removeLines[$row['line']] = true; // line numbers are 1-based
            } else {
                $seen[$row['key']] = $row['line'];
            }
        }

        if (empty($removeLines)) {
            return true; // nothing to do
        }

        $lines    = file($filePath); // preserves original line endings
        $filtered = [];

        foreach ($lines as $i => $line) {
            $lineNumber = $i + 1; // file() is 0-indexed, our line numbers are 1-based
            if (!isset($removeLines[$lineNumber])) {
                $filtered[] = $line;
            }
        }

        return file_put_contents($filePath, implode('', $filtered)) !== false;
    }
}
