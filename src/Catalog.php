<?php

declare(strict_types=1);

namespace DbCms\Installer;

final class Catalog
{
    public const LARAVEL_VERSION = 'v13.11.0';

    /** @var list<string> */
    public const THEMES = ['default-clean', 'db-brutalist', 'db-cyber', 'db-editorial', 'db-enterprise', 'db-minimal', 'db-sunset'];

    /** @var list<string> */
    public const MODULES = ['blog', 'gallery', 'popups', 'content-audit', 'analytics'];

    /** @var list<string> */
    public const ADMIN_THEMES = ['dbcms', 'termocms'];
}
