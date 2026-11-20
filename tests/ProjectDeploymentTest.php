<?php

declare(strict_types=1);

use DbCms\Installer\NewCommand;
use DbCms\Installer\ProjectCreator;
use DbCms\Installer\Selection;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\FakeRunner;

beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().'/dbcms-project-files-'.bin2hex(random_bytes(6));
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

it('allows omitting deployment while recording the public composition without copying secrets', function (): void {
    $this->runner->installed = [
        ['type' => 'dbcms-module', 'extra' => ['dbcms' => ['identifier' => 'client_area']]],
        ['type' => 'dbcms-block-package', 'extra' => ['dbcms' => ['identifier' => 'before_after']]],
    ];
    $app = $this->creator->create('site', new Selection(theme: 'db-minimal', adminTheme: 'termocms', deploy: 'none'), $this->directory);

    expect(file_exists($app.'/.github/workflows/deploy.yml'))->toBeFalse()
        ->and(file_get_contents($app.'/.env.example'))->toContain('CMS_THEME=db-minimal', 'CMS_BRAND=termocms', 'CMS_MODULES=client_area', 'CMS_BLOCK_PACKAGES=before_after')
        ->not->toContain('private-fixture-key')
        ->and(file_get_contents($app.'/.env'))->toBe('APP_KEY=private-fixture-key')
        ->and(file_get_contents($app.'/README.md'))->toContain('# site', 'dbcms:site:pending', 'php artisan serve', '/install', 'composer.lock', 'db-minimal', 'termocms', 'client_area', 'before_after')
        ->not->toContain('{{START_COMMAND}}', 'private-fixture-key', 'docs/DEPLOYMENT.md')
        ->and(file_get_contents($app.'/AGENTS.md'))->toContain('CORE_AND_EXTENSIONS.md', 'EXTENSIONS.md', 'CONVENTIONS.md', 'kit de administración')
        ->not->toContain('laravel/boost', 'private-fixture-key')
        ->and(file_get_contents($app.'/CLAUDE.md'))->toBe("@AGENTS.md\n")
        ->and(file_get_contents($app.'/GEMINI.md'))->toBe("@./AGENTS.md\n")
        ->and(file_get_contents($app.'/.gitignore'))->toBe("/.codex/*\n!/.codex/agents/\n/vendor\n")
        ->and(file_exists($app.'/.codex/agents/dbcms-extensiones.toml'))->toBeTrue()
        ->and(file_exists($app.'/.opencode/commands/dbcms.md'))->toBeTrue()
        ->and(file_exists($app.'/.gemini/commands/dbcms.toml'))->toBeTrue()
        ->and(file_exists($app.'/.agents/skills/dbcms-composicion/SKILL.md'))->toBeTrue();
});

it('generates the SSH workflow and its activation instructions by default in the CLI', function (): void {
    $tester = new CommandTester(new NewCommand($this->creator));
    expect($tester->execute(['directory' => $this->directory.'/site'], ['interactive' => false]))->toBe(0)
        ->and($tester->getDisplay())->toContain('.github/workflows/deploy.yml', 'docs/DEPLOYMENT.md');
    $app = $this->directory.'/site';
    expect(is_file($app.'/.github/workflows/deploy.yml'))->toBeTrue()
        ->and(file_get_contents($app.'/.github/workflows/deploy.yml'))->toContain("vars.DEPLOY_ENABLED == 'true'", 'environment: production', 'secrets.VPS_SSH_KEY')
        ->and(is_file($app.'/.github/scripts/deploy-remote.sh'))->toBeTrue()
        ->and(is_file($app.'/docker/deploy/Dockerfile'))->toBeTrue()
        ->and(is_file($app.'/app/Http/Controllers/DeploymentHealthController.php'))->toBeTrue()
        ->and(file_get_contents($app.'/routes/web.php'))->toContain('DeploymentHealthController')
        ->and(file_get_contents($app.'/docs/DEPLOYMENT.md'))->toContain('VPS_SSH_KEY', 'migrate --force', 'deployment-recovery-required')
        ->and(file_get_contents($app.'/README.md'))->toContain('docs/DEPLOYMENT.md');
});

it('rejects an unknown deployment type before creating anything', function (): void {
    expect(fn () => new Selection(deploy: 'other'))->toThrow(InvalidArgumentException::class);
    expect($this->runner->calls)->toBeEmpty();
});

it('does not follow symbolic links when generating project files', function (string $path): void {
    mkdir($this->directory.'/outside');
    file_put_contents($this->directory.'/outside/keep', 'private');
    $this->runner->afterSetup = function (string $app) use ($path): void {
        if ($path === '.env.example') {
            unlink($app.'/.env.example');
            symlink($this->directory.'/outside/keep', $app.'/.env.example');
        } elseif ($path === 'AGENTS.md' || $path === 'CLAUDE.md') {
            symlink($this->directory.'/outside/keep', $app.'/'.$path);
        } else {
            symlink($this->directory.'/outside', $app.'/.github');
        }
    };

    expect(fn () => $this->creator->create('site', new Selection(deploy: 'ssh'), $this->directory))->toThrow(RuntimeException::class);
    expect(file_exists($this->directory.'/site'))->toBeFalse()
        ->and(file_get_contents($this->directory.'/outside/keep'))->toBe('private')
        ->and(scandir($this->directory.'/outside'))->toBe(['.', '..', 'keep']);
})->with(['.env.example', 'AGENTS.md', 'CLAUDE.md', '.opencode', '.gemini', '.codex', '.agents', '.github']);

it('preserves a conflicting deployment file and rejects the partial project', function (): void {
    $this->runner->afterSetup = static function (string $app): void {
        mkdir($app.'/.github/workflows', 0700, true);
        file_put_contents($app.'/.github/workflows/deploy.yml', 'project-owned');
    };

    expect(fn () => $this->creator->create('site', new Selection(deploy: 'ssh'), $this->directory))->toThrow(RuntimeException::class);
    expect(file_exists($this->directory.'/site'))->toBeFalse();
    $staging = glob($this->directory.'/.dbcms-site-*')[0];
    expect(file_get_contents($staging.'/.github/workflows/deploy.yml'))->toBe('project-owned');
});
