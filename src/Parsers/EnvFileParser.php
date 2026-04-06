<?php

declare(strict_types=1);

namespace VaultCheck\Parsers;

/**
 * Parses .env files into key => value maps.
 * Handles: comments, blank lines, quoted values, inline comments, export prefix.
 */
class EnvFileParser
{
    /**
     * Parse a .env file and return key => value pairs.
     * Preserves duplicate keys (last value wins) — duplicates detected separately.
     *
     * @return array<string, string>
     */
    public function parse(string $filePath): array
    {
        if (!is_file($filePath)) {
            return [];
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        $result = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Skip blank lines and comments
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            // Strip optional `export ` prefix
            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = substr($trimmed, 7);
            }

            if (!str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $rawValue] = explode('=', $trimmed, 2);
            $key = trim($key);

            if ($key === '') {
                continue;
            }

            $result[$key] = $this->parseValue($rawValue);
        }

        return $result;
    }

    /**
     * Parse raw lines including duplicates.
     *
     * @return array<int, array{key: string, value: string, line: int}>
     */
    public function parseWithLineNumbers(string $filePath): array
    {
        if (!is_file($filePath)) {
            return [];
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES);
        $result = [];

        foreach ($lines as $lineNumber => $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = substr($trimmed, 7);
            }

            if (!str_contains($trimmed, '=')) {
                continue;
            }

            [$key, $rawValue] = explode('=', $trimmed, 2);
            $key = trim($key);

            if ($key === '') {
                continue;
            }

            $result[] = [
                'key'   => $key,
                'value' => $this->parseValue($rawValue),
                'line'  => $lineNumber + 1,
            ];
        }

        return $result;
    }

    /**
     * Returns raw file content for checks that need it (e.g. line ending detection).
     */
    public function rawContent(string $filePath): string
    {
        if (!is_file($filePath)) {
            return '';
        }
        return file_get_contents($filePath);
    }

    private function parseValue(string $raw): string
    {
        $raw = trim($raw);

        // Quoted value — strip quotes and unescape
        if (
            (str_starts_with($raw, '"') && str_ends_with($raw, '"')) ||
            (str_starts_with($raw, "'") && str_ends_with($raw, "'"))
        ) {
            return substr($raw, 1, -1);
        }

        // Strip inline comment (unquoted)
        if (str_contains($raw, ' #')) {
            $raw = trim(explode(' #', $raw, 2)[0]);
        }

        return $raw;
    }
}
