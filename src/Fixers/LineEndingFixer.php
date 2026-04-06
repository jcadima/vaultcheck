<?php

declare(strict_types=1);

namespace VaultCheck\Fixers;

/**
 * Fixes E010 (Windows-style CRLF line endings) by converting to Unix LF.
 */
class LineEndingFixer
{
    /**
     * Strip \r characters from the file, rewriting it in place.
     * Converts both \r\n (Windows) and lone \r (old Mac) to \n.
     */
    public function fix(string $filePath): bool
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return false;
        }

        $fixed = str_replace(["\r\n", "\r"], "\n", $content);
        return file_put_contents($filePath, $fixed) !== false;
    }
}
