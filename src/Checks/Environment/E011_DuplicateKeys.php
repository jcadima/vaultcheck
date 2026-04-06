<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;
use VaultCheck\Parsers\EnvFileParser;

class E011_DuplicateKeys extends BaseCheck
{
    public function __construct(private readonly EnvFileParser $parser) {}

    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath());
    }

    public function run(ScanContext $context): FindingCollection
    {
        $rows = $this->parser->parseWithLineNumbers($context->envFilePath());

        $seen = [];
        $col  = new FindingCollection();

        foreach ($rows as $row) {
            $key = $row['key'];

            if (isset($seen[$key])) {
                $col->add($this->finding(
                    'E011',
                    Finding::SEVERITY_MEDIUM,
                    "Duplicate key '{$key}' on line {$row['line']} (first seen on line {$seen[$key]}).",
                    "Remove the duplicate '{$key}' entry from .env.",
                    $context->envFilePath(),
                    $row['line'],
                ));
            } else {
                $seen[$key] = $row['line'];
            }
        }

        return $col;
    }
}
