<?php

declare(strict_types=1);

namespace VaultCheck\Engine;

class FindingCollection implements \Countable, \IteratorAggregate
{
    /** @var Finding[] */
    private array $findings = [];

    public function add(Finding $finding): void
    {
        $this->findings[] = $finding;
    }

    public function merge(self $other): void
    {
        foreach ($other as $finding) {
            $this->add($finding);
        }
    }

    public function sortBySeverity(): self
    {
        $sorted = clone $this;
        usort($sorted->findings, fn(Finding $a, Finding $b) =>
            $b->severityScore() <=> $a->severityScore()
        );
        return $sorted;
    }

    public function hasMediumOrAbove(): bool
    {
        foreach ($this->findings as $finding) {
            if ($finding->isMediumOrAbove()) {
                return true;
            }
        }
        return false;
    }

    public function filterBySeverity(string $severity): self
    {
        $filtered = new self();
        foreach ($this->findings as $finding) {
            if ($finding->severity === $severity) {
                $filtered->add($finding);
            }
        }
        return $filtered;
    }

    public function isEmpty(): bool
    {
        return empty($this->findings);
    }

    public function count(): int
    {
        return count($this->findings);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->findings);
    }

    public function toArray(): array
    {
        return array_map(fn(Finding $f) => $f->toArray(), $this->findings);
    }
}
