<?php

declare(strict_types=1);

use DbCms\Installer\Selection;

it('asks Composer for Core and delegates the default composition to Core', function (): void {
    $selection = new Selection;
    expect($selection->requirements)->toBe(['dbravoan/dbcms-core:~0.0.2'])->and($selection->packages)->toBeEmpty();
});

it('combines comma lists and repeated options in order across all selectors', function (): void {
    $selection = new Selection([
        'theme' => ['db-editorial, acme/theme:^2', 'db-cyber'],
        'admin-theme' => ['termocms,acme/admin'],
        'module' => ['blog:>=0.0.1 <0.1,gallery', 'blog'],
        'block-package' => ['before-after,acme/blocks'],
        'package' => ['community/client-area:^1.2,acme/generic', 'acme/theme'],
    ]);

    expect($selection->requirements)->toBe([
        'dbravoan/dbcms-core:~0.0.2',
        'dbravoan/dbcms-theme-db-editorial', 'acme/theme:^2', 'dbravoan/dbcms-theme-db-cyber',
        'dbravoan/dbcms-admin-theme-termocms', 'acme/admin',
        'dbravoan/dbcms-module-blog:>=0.0.1 <0.1', 'dbravoan/dbcms-module-gallery',
        'dbravoan/dbcms-blocks-before-after', 'acme/blocks', 'community/client-area:^1.2', 'acme/generic',
    ])->and($selection->packages['theme'])->toBe(['dbravoan/dbcms-theme-db-editorial', 'acme/theme', 'dbravoan/dbcms-theme-db-cyber'])
        ->and($selection->packages['package'])->toBe(['community/client-area', 'acme/generic', 'acme/theme'])
        ->and($selection->abbreviations)->toBe([
            'dbravoan/dbcms-theme-db-editorial', 'dbravoan/dbcms-theme-db-cyber', 'dbravoan/dbcms-admin-theme-termocms',
            'dbravoan/dbcms-module-blog', 'dbravoan/dbcms-module-gallery', 'dbravoan/dbcms-blocks-before-after',
        ]);
});

it('preserves the explicit restriction without duplicating package arguments', function (): void {
    expect((new Selection(['module' => ['blog,blog:~0.0.1'], 'package' => ['dbravoan/dbcms-module-blog:~0.0.1']]))->requirements)
        ->toBe(['dbravoan/dbcms-core:~0.0.2', 'dbravoan/dbcms-module-blog:~0.0.1']);
    expect(fn () => new Selection(['module' => ['blog:^1'], 'package' => ['dbravoan/dbcms-module-blog:^2']]))
        ->toThrow(InvalidArgumentException::class, 'otra restricción');
});

it('rejects invalid syntax and package abbreviations without a type', function (string $option, string $value): void {
    expect(fn () => new Selection([$option => [$value]]))->toThrow(InvalidArgumentException::class, "Cada entrada de --{$option}");
})->with([
    ['package', 'blog'], ['package', 'community/client-area,blog'], ['package', "vendor/name\n--bad"],
    ['module', 'blog,'], ['module', 'blog:'], ['theme', '../bad'], ['admin-theme', '--bad'],
]);
