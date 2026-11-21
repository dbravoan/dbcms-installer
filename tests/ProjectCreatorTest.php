<?php

declare(strict_types=1);

use DbCms\Installer\ProjectCreator;
use DbCms\Installer\Selection;
use Tests\FakeRunner;

beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().'/dbcms-installer-test-'.bin2hex(random_bytes(6));
    mkdir($this->directory, 0700);
    $this->runner = new FakeRunner;
    $this->creator = new ProjectCreator($this->runner);
});

afterEach(function (): void {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $file) {
        $file->isDir() && ! $file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($this->directory);
});

it('creates a project atomically and delegates setup without migrations', function (): void {
    $this->runner->composition['modules'] = ['client_area', 'blog'];
    $this->runner->composition['blocks'] = ['before_after'];
    $result = $this->creator->create('site', new Selection(['package' => ['community/client-area']]), $this->directory);
    expect($result)->toBe($this->directory.'/site')->and(is_file($result.'/composer.json'))->toBeTrue();
    expect(file_get_contents($result.'/.env.example'))->toContain("APP_NAME=dbCMS\n")->not->toContain('APP_NAME=Laravel');
    $commands = array_column($this->runner->calls, 'command');
    $setupPosition = array_find_key($commands, fn (array $command): bool => in_array('--theme=default-clean', $command, true));
    $setup = $commands[$setupPosition];
    expect($setup)->toContain('--modules=client_area,blog', '--blocks=before_after', '--theme=default-clean', '--admin-theme=dbcms');
    $autoloadPosition = array_search(['composer', 'dump-autoload', '--no-scripts', '--no-interaction'], $commands, true);
    $buildPosition = array_search(['npm', 'run', 'build'], $commands, true);
    expect($autoloadPosition)->toBeInt()->toBeGreaterThan($setupPosition)->toBeLessThan($buildPosition);
    expect(array_filter($commands, fn (array $command): bool => in_array('migrate', $command, true) || in_array('cms:install', $command, true)))->toBeEmpty();
    expect(glob($this->directory.'/.dbcms-*'))->toBeEmpty();
});

it('rejects existing directories and symbolic links before spawning processes', function (): void {
    mkdir($this->directory.'/existing');
    file_put_contents($this->directory.'/existing/keep', 'data');
    symlink($this->directory.'/missing', $this->directory.'/linked');
    foreach (['existing', 'linked'] as $target) {
        expect(fn () => $this->creator->create($target, new Selection, $this->directory))->toThrow(InvalidArgumentException::class, 'ya existe');
    }
    expect($this->runner->calls)->toBeEmpty()->and(file_get_contents($this->directory.'/existing/keep'))->toBe('data');
});

it('leaves only a private diagnostic on failure and never renames a partial application', function (): void {
    $this->runner->failureCommand = 'build';
    expect(fn () => $this->creator->create('failed', new Selection, $this->directory))->toThrow(RuntimeException::class, 'compilar');
    expect(file_exists($this->directory.'/failed'))->toBeFalse();
    $diagnostic = glob($this->directory.'/.dbcms-failed-*/.dbcms-install-failed.json')[0];
    expect(file_get_contents($diagnostic))->not->toContain('must-not-leak')->and(fileperms($diagnostic) & 0777)->toBe(0600);
});

it('checks Composer and Node before downloading anything', function (): void {
    $this->runner->composerVersion = 'Composer version 1.10.0';
    expect(fn () => $this->creator->create('bad-composer', new Selection, $this->directory))->toThrow(RuntimeException::class, 'Composer 2');
    $this->runner->composerVersion = 'Composer version 2.9.0';
    foreach (['v20.0.0', 'v22.0.0', 'v22.11.0', 'unknown'] as $version) {
        $this->runner->nodeVersion = $version;
        expect(fn () => $this->creator->create('bad-node', new Selection, $this->directory))->toThrow(RuntimeException::class, 'Node.js 22');
    }
    expect(glob($this->directory.'/.dbcms-*'))->toBeEmpty();
});

it('only prepares Sail when requested', function (): void {
    $this->creator->create('sail-site', new Selection(sail: true), $this->directory);
    $commands = array_column($this->runner->calls, 'command');
    expect(array_values(array_filter($commands, fn (array $command): bool => in_array('sail:install', $command, true))))->toHaveCount(1);
    expect(file_get_contents($this->directory.'/sail-site/.env.example'))->toContain("APP_NAME=dbCMS\n");
    expect(file_get_contents($this->directory.'/sail-site/README.md'))->toContain('# sail-site', './vendor/bin/sail up -d', './vendor/bin/sail npm ci', './vendor/bin/sail npm run build', 'Ninguno adicional')
        ->not->toContain('php artisan serve', '{{START_COMMAND}}', '{{NODE_PREFIX}}');
});

it('preserves a custom application name in the environment example', function (): void {
    $this->runner->afterSetup = function (string $project): void {
        file_put_contents($project.'/.env.example', "APP_NAME=\"Área Bravo\"\nAPP_KEY=\n");
    };

    $project = $this->creator->create('site', new Selection, $this->directory);

    expect(file_get_contents($project.'/.env.example'))->toContain("APP_NAME=\"Área Bravo\"\n")->not->toContain('APP_NAME=dbCMS');
});

it('uses the default Composer stability when creating a project', function (): void {
    $this->creator->create('stable-site', new Selection, $this->directory);
    expect(array_filter($this->runner->calls, fn (array $call): bool => in_array('minimum-stability', $call['command'], true)))->toBeEmpty();
});

it('rejects malformed composition before writing the application', function (): void {
    $this->runner->composition['modules'] = ['../bad'];
    expect(fn () => $this->creator->create('bad-extension', new Selection, $this->directory))->toThrow(RuntimeException::class);
    expect(file_exists($this->directory.'/bad-extension'))->toBeFalse();
    expect(array_filter($this->runner->calls, fn (array $call): bool => in_array('--theme=default-clean', $call['command'], true)))->toBeEmpty();
});

it('installs the fallback named by Core and resolves the composition again', function (): void {
    $this->runner->requiredThemePackage = 'community/default';
    $this->creator->create('fallback', new Selection, $this->directory);
    $commands = array_column($this->runner->calls, 'command');
    $requires = array_values(array_filter($commands, fn (array $command): bool => $command[1] === 'require'));
    expect($requires)->toHaveCount(2)
        ->and($requires[0])->toContain('dbravoan/dbcms-core:~0.0.2')->not->toContain('community/default')
        ->and($requires[1])->toContain('community/default');
    expect(array_filter($commands, fn (array $command): bool => in_array('--resolve-packages={}', $command, true)))->toHaveCount(2);
});

it('probes missing shorthand names only after failure and lets Composer report full names', function (string $flag, string $value, string $message, bool $probe): void {
    $name = 'dbravoan/dbcms-module-missing';
    $this->runner->missingPackages = [$name];
    $selection = new Selection([$flag => [$value]]);
    expect(fn () => $this->creator->create('missing', $selection, $this->directory))->toThrow(RuntimeException::class, $message);
    expect(array_filter($this->runner->calls, fn (array $call): bool => $call['command'][1] === 'show'))->toHaveCount($probe ? 1 : 0);
})->with([
    ['module', 'missing', 'No se encuentra el paquete dbravoan/dbcms-module-missing', true],
    ['package', 'dbravoan/dbcms-module-missing', 'Composer no pudo resolver las dependencias', false],
]);
