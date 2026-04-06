<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Environment;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;
use VaultCheck\Parsers\EnvFileParser;

class E010_WindowsLineEndings extends BaseCheck
{
    public function __construct(private readonly EnvFileParser $parser) {}

    public function isApplicable(ScanContext $context): bool
    {
        return is_file($context->envFilePath());
    }

    public function run(ScanContext $context): FindingCollection
    {
        $content = $this->parser->rawContent($context->envFilePath());

        if (str_contains($content, "\r\n") || str_contains($content, "\r")) {
            return $this->collection(
                $this->finding(
                    'E010',
                    Finding::SEVERITY_LOW,
                    '.env file contains Windows-style line endings (CRLF). This can cause parsing issues on Linux.',
                    'Convert to Unix line endings: sed -i \'s/\r//\' .env',
                    $context->envFilePath(),
                )
            );
        }

        return $this->empty();
    }
}
