<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Engine;

use PHPUnit\Framework\TestCase;
use VaultCheck\Engine\Finding;

class FindingTest extends TestCase
{
    public function test_severity_constants_are_defined(): void
    {
        $this->assertSame('CRITICAL', Finding::SEVERITY_CRITICAL);
        $this->assertSame('HIGH',     Finding::SEVERITY_HIGH);
        $this->assertSame('MEDIUM',   Finding::SEVERITY_MEDIUM);
        $this->assertSame('LOW',      Finding::SEVERITY_LOW);
        $this->assertSame('INFO',     Finding::SEVERITY_INFO);
    }

    public function test_severity_score_ordering(): void
    {
        $critical = new Finding('T001', Finding::SEVERITY_CRITICAL, 'msg');
        $high     = new Finding('T001', Finding::SEVERITY_HIGH,     'msg');
        $medium   = new Finding('T001', Finding::SEVERITY_MEDIUM,   'msg');
        $low      = new Finding('T001', Finding::SEVERITY_LOW,      'msg');
        $info     = new Finding('T001', Finding::SEVERITY_INFO,     'msg');

        $this->assertGreaterThan($high->severityScore(),   $critical->severityScore());
        $this->assertGreaterThan($medium->severityScore(), $high->severityScore());
        $this->assertGreaterThan($low->severityScore(),    $medium->severityScore());
        $this->assertGreaterThan($info->severityScore(),   $low->severityScore());
    }

    public function test_is_medium_or_above_for_high_severity(): void
    {
        $finding = new Finding('T001', Finding::SEVERITY_HIGH, 'msg');
        $this->assertTrue($finding->isMediumOrAbove());
    }

    public function test_is_medium_or_above_for_medium_severity(): void
    {
        $finding = new Finding('T001', Finding::SEVERITY_MEDIUM, 'msg');
        $this->assertTrue($finding->isMediumOrAbove());
    }

    public function test_is_not_medium_or_above_for_low_severity(): void
    {
        $finding = new Finding('T001', Finding::SEVERITY_LOW, 'msg');
        $this->assertFalse($finding->isMediumOrAbove());
    }

    public function test_to_array_contains_all_fields(): void
    {
        $finding = new Finding('E001', Finding::SEVERITY_HIGH, 'A message', 'A suggestion', '/path/to/file', 42);

        $arr = $finding->toArray();

        $this->assertSame('E001',            $arr['check_id']);
        $this->assertSame('HIGH',            $arr['severity']);
        $this->assertSame('A message',       $arr['message']);
        $this->assertSame('A suggestion',    $arr['suggestion']);
        $this->assertSame('/path/to/file',   $arr['file']);
        $this->assertSame(42,                $arr['line']);
    }

    public function test_to_array_optional_fields_default_to_null(): void
    {
        $finding = new Finding('E001', Finding::SEVERITY_LOW, 'msg');

        $arr = $finding->toArray();

        $this->assertNull($arr['file']);
        $this->assertNull($arr['line']);
        $this->assertSame('', $arr['suggestion']);
    }
}
