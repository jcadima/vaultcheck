<?php

declare(strict_types=1);

namespace VaultCheck\Fixers;

/**
 * Fixes file permission findings by setting the .env file to owner-only access.
 * Handles P001 (world-readable), P002 (world-writable), and P003 (group-writable).
 */
class PermissionFixer
{
    /**
     * Set .env to 0600 (owner read+write only). Fixes P001 and P002.
     */
    public function fixStrict(string $filePath): bool
    {
        return chmod($filePath, 0600);
    }

    /**
     * Set .env to 0640 (owner read+write, group read). Fixes P003.
     */
    public function fixGroupWrite(string $filePath): bool
    {
        return chmod($filePath, 0640);
    }
}
