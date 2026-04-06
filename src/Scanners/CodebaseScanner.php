<?php

declare(strict_types=1);

namespace VaultCheck\Scanners;

use Symfony\Component\Finder\Finder;

/**
 * Scans PHP files in the project for env() / getenv() / $_ENV usages.
 * Returns a map of KEY => list of usages with file, line, and hasDefault.
 */
class CodebaseScanner
{
    private const SKIP_DIRS = [
        'vendor',
        'node_modules',
        '.git',
        'storage',
        'bootstrap/cache',
    ];

    /**
     * @return array<string, list<array{file: string, line: int, hasDefault: bool}>>
     */
    public function scan(string $projectPath): array
    {
        if (!is_dir($projectPath)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()
            ->in($projectPath)
            ->name('*.php')
            ->exclude(self::SKIP_DIRS);

        $refs = [];

        foreach ($finder as $file) {
            $this->extractFromFile($file->getRealPath(), $refs);
        }

        return $refs;
    }

    private function extractFromFile(string $filePath, array &$refs): void
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $lineIndex => $line) {
            $lineNumber = $lineIndex + 1;

            // Match env('KEY') or env('KEY', default) or env("KEY") etc.
            if (preg_match_all(
                '/\benv\s*\(\s*[\'"]([A-Z][A-Z0-9_]*)[\'"](\s*,\s*[^)]+)?\s*\)/',
                $line,
                $matches,
                PREG_SET_ORDER,
            )) {
                foreach ($matches as $match) {
                    $key        = $match[1];
                    $hasDefault = isset($match[2]) && trim($match[2]) !== '';
                    $refs[$key][] = [
                        'file'       => $filePath,
                        'line'       => $lineNumber,
                        'hasDefault' => $hasDefault,
                    ];
                }
            }

            // Match getenv('KEY') or getenv("KEY")
            if (preg_match_all(
                '/\bgetenv\s*\(\s*[\'"]([A-Z][A-Z0-9_]*)[\'"]/',
                $line,
                $matches,
                PREG_SET_ORDER,
            )) {
                foreach ($matches as $match) {
                    $key = $match[1];
                    $refs[$key][] = [
                        'file'       => $filePath,
                        'line'       => $lineNumber,
                        'hasDefault' => false,
                    ];
                }
            }

            // Match $_ENV['KEY'] or $_ENV["KEY"]
            if (preg_match_all(
                '/\$_ENV\s*\[\s*[\'"]([A-Z][A-Z0-9_]*)[\'"]/',
                $line,
                $matches,
                PREG_SET_ORDER,
            )) {
                foreach ($matches as $match) {
                    $key = $match[1];
                    $refs[$key][] = [
                        'file'       => $filePath,
                        'line'       => $lineNumber,
                        'hasDefault' => false,
                    ];
                }
            }
        }
    }
}
