<?php

declare(strict_types=1);

namespace VaultCheck\Patterns;

/**
 * Describes a single known secret pattern (Stripe key, AWS key, etc.)
 */
readonly class SecretPattern
{
    public function __construct(
        public string $name,
        public string $regex,
        public string $service,
        public string $rotationUrl = '',
    ) {}
}
