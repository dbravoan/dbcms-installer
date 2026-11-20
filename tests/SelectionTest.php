<?php

declare(strict_types=1);

use DbCms\Installer\Selection;

it('installs only core and the reference theme by default', function (): void {
    expect((new Selection)->requirements())->toBe(['dbravoan/dbcms-core:~0.0.1', 'dbravoan/dbcms-theme-default-clean:~0.0.1']);
});

it('expresses selected optional packages as real Composer requirements', function (): void {
    $selection = new Selection('db-minimal', 'termocms', ['blog', 'gallery', 'blog'], ['community/client-area:^1.2'], beforeAfter: true);
    expect($selection->requirements())->toBe([
        'dbravoan/dbcms-core:~0.0.1', 'dbravoan/dbcms-theme-db-minimal:~0.0.1',
        'dbravoan/dbcms-admin-theme-termocms:~0.0.1', 'dbravoan/dbcms-module-blog:~0.0.1',
        'dbravoan/dbcms-module-gallery:~0.0.1', 'dbravoan/dbcms-blocks-before-after:~0.0.1',
        'community/client-area:^1.2',
    ]);
});

it('lets Composer select the version of an arbitrary vendor package', function (): void {
    expect((new Selection(additionalPackages: ['other-vendor/account']))->requirements())->toContain('other-vendor/account');
});

it('selects themes from another vendor through their installed identifiers', function (): void {
    $selection = new Selection('community-clean', 'community-admin', additionalPackages: ['community/theme:^2', 'community/admin:^2']);
    expect($selection->requirements())->toBe(['dbravoan/dbcms-core:~0.0.1', 'community/theme:^2', 'community/admin:^2']);
});

it('rejects modules outside the official catalogue', function (): void {
    expect(fn () => new Selection(modules: ['shop']))->toThrow(InvalidArgumentException::class, 'Módulo oficial desconocido: shop');
});

it('rejects invalid names and conflicting core restrictions', function (): void {
    expect(fn () => new Selection(additionalPackages: ["vendor/name\n--bad"]))->toThrow(InvalidArgumentException::class);
    expect(fn () => (new Selection(additionalPackages: ['dbravoan/dbcms-core:^3']))->requirements())->toThrow(InvalidArgumentException::class, 'otra restricción');
    expect(fn () => new Selection(modules: ['unknown']))->toThrow(InvalidArgumentException::class);
});
