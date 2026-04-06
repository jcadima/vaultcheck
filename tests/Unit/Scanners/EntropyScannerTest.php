<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Scanners;

use PHPUnit\Framework\TestCase;
use VaultCheck\Scanners\EntropyScanner;

class EntropyScannerTest extends TestCase
{
    private EntropyScanner $scanner;

    protected function setUp(): void
    {
        $this->scanner = new EntropyScanner();
    }

    public function test_npm_sha512_integrity_hash_is_known_safe(): void
    {
        $this->assertTrue(
            $this->scanner->isKnownSafe('sha512-abc123DEFghiJKLmnoPQRstuVWXyz/+0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdef=='),
            'npm sha512 integrity hash should be treated as safe'
        );
    }

    public function test_npm_sha256_integrity_hash_is_known_safe(): void
    {
        $this->assertTrue(
            $this->scanner->isKnownSafe('sha256-AbCdEfGhIjKlMnOpQrStUvWxYz0123456789+/=='),
            'npm sha256 integrity hash should be treated as safe'
        );
    }

    public function test_npm_sha1_integrity_hash_is_known_safe(): void
    {
        $this->assertTrue(
            $this->scanner->isKnownSafe('sha1-AbCdEfGhIjKlMnOpQrStUvWxYz01234='),
            'npm sha1 integrity hash should be treated as safe'
        );
    }

    public function test_real_secret_is_not_known_safe(): void
    {
        $this->assertFalse(
            $this->scanner->isKnownSafe('FAKE_xK9mVb3nLpQr2sT8wUyZ4aC5eHjD'),
            'High-entropy token with no safe prefix should not be marked as safe'
        );
    }
}
