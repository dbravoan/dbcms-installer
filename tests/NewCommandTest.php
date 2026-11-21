<?php

declare(strict_types=1);

use DbCms\Installer\NewCommand;
use DbCms\Installer\ProjectCreator;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\FakeRunner;

it('supports an unattended CLI installation with explicit optional packages', function (?string $deployment): void {
    $directory = sys_get_temp_dir().'/dbcms-cli-test-'.bin2hex(random_bytes(6));
    mkdir($directory, 0700);
    $runner = new FakeRunner;
    $tester = new CommandTester(new NewCommand(new ProjectCreator($runner)));
    try {
        $input = ['directory' => $directory.'/site', '--module' => ['blog,gallery', 'content-audit'], '--package' => ['other-vendor/client-area:^1,other-vendor/tools']];
        if ($deployment !== null) {
            $input['--deploy'] = $deployment;
        }
        $code = $tester->execute($input, ['interactive' => false]);
        expect($code)->toBe(0)->and($tester->getDisplay())->toContain('Aplicación creada', '/install');
        expect(is_file($directory.'/site/.github/workflows/deploy.yml'))->toBe($deployment !== 'none');
        $require = array_values(array_filter($runner->calls, fn (array $call): bool => ($call['command'][1] ?? null) === 'require'))[0]['command'];
        expect($require)->toContain('dbravoan/dbcms-module-blog', 'dbravoan/dbcms-module-gallery', 'dbravoan/dbcms-module-content-audit', 'other-vendor/client-area:^1', 'other-vendor/tools');
    } finally {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($directory);
    }
})->with(['por defecto' => [null], 'sin workflow' => ['none']]);

it('returns a failure code and a readable error for invalid selection', function (): void {
    $runner = new FakeRunner;
    $tester = new CommandTester(new NewCommand(new ProjectCreator($runner)));
    expect($tester->execute(['directory' => '/unused', '--package' => ['blog']], ['interactive' => false]))->toBe(1);
    expect($tester->getDisplay())->toContain('Cada entrada de --package debe ser vendor/paquete');
    expect($runner->calls)->toBeEmpty();
});
