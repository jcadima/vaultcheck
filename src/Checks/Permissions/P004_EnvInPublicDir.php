<?php

declare(strict_types=1);

namespace VaultCheck\Checks\Permissions;

use VaultCheck\Checks\BaseCheck;
use VaultCheck\Engine\Finding;
use VaultCheck\Engine\FindingCollection;
use VaultCheck\Engine\ScanContext;

class P004_EnvInPublicDir extends BaseCheck
{
    private const PUBLIC_DIRS = ['public', 'web', 'webroot', 'htdocs', 'www', 'public_html'];

    public function run(ScanContext $context): FindingCollection
    {
        $col = new FindingCollection();

        foreach (self::PUBLIC_DIRS as $dir) {
            $path = $context->projectPath . '/' . $dir . '/.env';
            if (is_file($path)) {
                $col->add($this->finding(
                    'P004',
                    Finding::SEVERITY_CRITICAL,
                    ".env file found inside web-accessible directory '{$dir}/.env'. It may be downloadable by anyone.",
                    "Delete {$dir}/.env immediately and ensure your web server blocks access to .env files.",
                    $path,
                ));
            }
        }

        return $col;
    }
}
