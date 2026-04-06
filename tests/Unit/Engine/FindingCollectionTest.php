<?php

declare(strict_types=1);

namespace VaultCheck\Tests\Unit\Engine;

use PHPUnit\Framework\TestCase;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;

class FindingCollectionTest extends TestCase
{
    public function test_starts_empty(): void
    {
        $col = new FindingCollection();
        $this->assertTrue($col->isEmpty());
        $this->assertSame(0, $col->count());
    }

    public function test_add_increments_count(): void
    {
        $col = new FindingCollection();
        $col->add(new Finding('E001', Finding::SEVERITY_HIGH, 'msg'));
        $this->assertSame(1, $col->count());
        $this->assertFalse($col->isEmpty());
    }

    public function test_merge_adds_all_findings(): void
    {
        $a = new FindingCollection();
        $a->add(new Finding('E001', Finding::SEVERITY_HIGH, 'a'));

        $b = new FindingCollection();
        $b->add(new Finding('E002', Finding::SEVERITY_LOW, 'b'));
        $b->add(new Finding('E003', Finding::SEVERITY_MEDIUM, 'c'));

        $a->merge($b);
        $this->assertSame(3, $a->count());
    }

    public function test_sort_by_severity_orders_critical_first(): void
    {
        $col = new FindingCollection();
        $col->add(new Finding('E001', Finding::SEVERITY_LOW,      'low'));
        $col->add(new Finding('E002', Finding::SEVERITY_CRITICAL, 'critical'));
        $col->add(new Finding('E003', Finding::SEVERITY_MEDIUM,   'medium'));

        $sorted = $col->sortBySeverity();
        $items  = iterator_to_array($sorted->getIterator());

        $this->assertSame('CRITICAL', $items[0]->severity);
        $this->assertSame('MEDIUM',   $items[1]->severity);
        $this->assertSame('LOW',      $items[2]->severity);
    }

    public function test_filter_by_severity(): void
    {
        $col = new FindingCollection();
        $col->add(new Finding('E001', Finding::SEVERITY_HIGH,   'high'));
        $col->add(new Finding('E002', Finding::SEVERITY_LOW,    'low'));
        $col->add(new Finding('E003', Finding::SEVERITY_HIGH,   'high2'));

        $highs = $col->filterBySeverity(Finding::SEVERITY_HIGH);
        $this->assertSame(2, $highs->count());

        $lows = $col->filterBySeverity(Finding::SEVERITY_LOW);
        $this->assertSame(1, $lows->count());
    }

    public function test_has_medium_or_above_returns_true_when_high_present(): void
    {
        $col = new FindingCollection();
        $col->add(new Finding('E001', Finding::SEVERITY_HIGH, 'msg'));
        $this->assertTrue($col->hasMediumOrAbove());
    }

    public function test_has_medium_or_above_returns_false_for_low_only(): void
    {
        $col = new FindingCollection();
        $col->add(new Finding('E001', Finding::SEVERITY_LOW, 'msg'));
        $this->assertFalse($col->hasMediumOrAbove());
    }

    public function test_to_array_serialises_all_findings(): void
    {
        $col = new FindingCollection();
        $col->add(new Finding('E001', Finding::SEVERITY_HIGH, 'msg'));

        $arr = $col->toArray();
        $this->assertCount(1, $arr);
        $this->assertSame('E001', $arr[0]['check_id']);
    }
}
