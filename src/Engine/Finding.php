<?php

declare(strict_types=1);

namespace VaultCheck\Engine;

class Finding
{
    public const SEVERITY_CRITICAL = 'CRITICAL';
    public const SEVERITY_HIGH     = 'HIGH';
    public const SEVERITY_MEDIUM   = 'MEDIUM';
    public const SEVERITY_LOW      = 'LOW';
    public const SEVERITY_INFO     = 'INFO';

    private static array $severityOrder = [
        self::SEVERITY_CRITICAL => 5,
        self::SEVERITY_HIGH     => 4,
        self::SEVERITY_MEDIUM   => 3,
        self::SEVERITY_LOW      => 2,
        self::SEVERITY_INFO     => 1,
    ];

    public function __construct(
        public readonly string $checkId,
        public readonly string $severity,
        public readonly string $message,
        public readonly string $suggestion = '',
        public readonly ?string $file = null,
        public readonly ?int $line = null,
    ) {}

    public function severityScore(): int
    {
        return self::$severityOrder[$this->severity] ?? 0;
    }

    public static function scoreFor(string $severity): int
    {
        return self::$severityOrder[$severity] ?? 0;
    }

    public static function isValidSeverity(string $severity): bool
    {
        return isset(self::$severityOrder[$severity]);
    }

    public function isMediumOrAbove(): bool
    {
        return $this->severityScore() >= self::$severityOrder[self::SEVERITY_MEDIUM];
    }

    public function toArray(): array
    {
        return [
            'check_id'   => $this->checkId,
            'severity'   => $this->severity,
            'message'    => $this->message,
            'suggestion' => $this->suggestion,
            'file'       => $this->file,
            'line'       => $this->line,
        ];
    }
}
