<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

beforeEach(function (): void {
    $this->root = sys_get_temp_dir().'/dbcms-deploy-test-'.bin2hex(random_bytes(6));
    $this->upload = sys_get_temp_dir().'/dbcms-upload.'.bin2hex(random_bytes(8));
    foreach ([$this->root.'/site', $this->root.'/source/vendor', $this->root.'/source/public/build', $this->root.'/bin', $this->upload] as $directory) {
        mkdir($directory, 0700, true);
    }
    $this->sha = str_repeat('a', 40);
    $this->release = 'release-'.$this->sha.'-1-1';
    $this->site = $this->root.'/site';
    $this->source = $this->root.'/source';
    $this->artifact = $this->upload.'/application.tar.gz';
    file_put_contents($this->source.'/artisan', '<?php');
    file_put_contents($this->source.'/vendor/autoload.php', '<?php');
    file_put_contents($this->source.'/public/build/manifest.json', '{}');
    file_put_contents($this->source.'/DEPLOYMENT_ID', $this->sha."\n");
    file_put_contents($this->site.'/.env', 'APP_KEY=base64:'.base64_encode(str_repeat('x', 32))."\nAPP_ENV=production\nAPP_DEBUG=false\nAPP_MAINTENANCE_DRIVER=file\nCACHE_STORE=file\nSESSION_DRIVER=file\nQUEUE_CONNECTION=sync\n");
    $php = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail
if [[ $1 == '-r' ]]; then exec __PHP__ "$@"; fi
command=$2
printf '%s\n' "$command" >> "$TEST_LOG"
if [[ $command == down ]]; then mkdir -p "$TEST_SITE/storage/framework"; printf '{}' > "$TEST_SITE/storage/framework/down"; fi
if [[ $command == up ]]; then rm -f "$TEST_SITE/storage/framework/down"; fi
if [[ $command == migrate && ${FAIL_MIGRATE:-0} == 1 ]]; then exit 10; fi
BASH;
    file_put_contents($this->root.'/bin/php', str_replace('__PHP__', escapeshellarg(PHP_BINARY), $php));
    file_put_contents($this->root.'/bin/curl', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail
url=${!#}
case "$url" in
  */_deployment/health*) printf '%s\n200' "${WRONG_IDENTITY:-$TEST_SHA}" ;;
  */up|*/install) printf 200 ;;
esac
BASH);
    file_put_contents($this->root.'/bin/sleep', "#!/usr/bin/env bash\nexit 0\n");
    file_put_contents($this->root.'/bin/sudo', "#!/usr/bin/env bash\nprintf 'unexpected sudo\\n' >> \"\$TEST_LOG\"\n");
    foreach (['php', 'curl', 'sleep', 'sudo'] as $name) {
        chmod($this->root.'/bin/'.$name, 0755);
    }
    $this->environment = ['PATH' => $this->root.'/bin:'.getenv('PATH'), 'TEST_LOG' => $this->root.'/commands', 'TEST_SITE' => $this->site, 'TEST_SHA' => $this->sha];
    $this->pack = function (): void {
        if (! is_dir($this->upload)) {
            mkdir($this->upload, 0700);
        }
        (new Process(['tar', '-czf', $this->artifact, '-C', $this->source, '.']))->mustRun();
    };
});

afterEach(function (): void {
    foreach ([$this->root, $this->upload] as $directory) {
        if (! is_dir($directory)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            $file->isDir() && ! $file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($directory);
    }
});

function runDeployment(object $test, array $environment = [], string $fpmService = ''): Process
{
    $process = new Process(['bash', dirname(__DIR__).'/stubs/ssh/.github/scripts/deploy-remote.sh', $test->site, $test->release, $test->sha, 'https://example.test', $test->root.'/bin/php', $fpmService, $test->artifact], env: array_replace($test->environment, $environment));
    $process->run();

    return $process;
}

function installPreviousDeployment(object $test): string
{
    $previous = $test->site.'/releases/release-'.str_repeat('b', 40).'-1-1';
    mkdir($previous, 0700, true);
    file_put_contents($previous.'/artisan', '<?php');
    mkdir($test->site.'/storage/framework', 0700, true);
    file_put_contents($test->site.'/storage/installed', 'installed');
    file_put_contents($test->site.'/storage/keep', 'private data');
    mkdir($test->site.'/uploads');
    file_put_contents($test->site.'/uploads/keep', 'public media');
    symlink($previous, $test->site.'/current');

    return $previous;
}

it('prepares a first installation without database commands and preserves server configuration', function (): void {
    $before = file_get_contents($this->site.'/.env');
    ($this->pack)();
    $result = runDeployment($this);

    expect($result->isSuccessful())->toBeTrue($result->getErrorOutput())
        ->and(realpath($this->site.'/current'))->toBe($this->site.'/releases/'.$this->release)
        ->and(file_get_contents($this->site.'/.env'))->toBe($before)
        ->and(file_get_contents($this->root.'/commands'))->not->toMatch('/migrate|cms:install|cms:access:sync|:cache/')
        ->and(file_exists($this->site.'/deployment-recovery-required'))->toBeFalse();
});

it('updates an installed application in maintenance and keeps its data and previous code', function (): void {
    $previous = installPreviousDeployment($this);
    ($this->pack)();
    $result = runDeployment($this);

    expect($result->isSuccessful())->toBeTrue($result->getErrorOutput())
        ->and(file_get_contents($this->root.'/commands'))->toContain("down\nmigrate\ncms:access:sync\n")
        ->toContain('config:cache', 'route:cache', 'view:cache', 'event:cache')
        ->and(file_get_contents($this->site.'/storage/keep'))->toBe('private data')
        ->and(file_get_contents($this->site.'/uploads/keep'))->toBe('public media')
        ->and(is_dir($previous))->toBeTrue()
        ->and(file_exists($this->site.'/storage/framework/down'))->toBeFalse()
        ->and(realpath($this->site.'/current/storage'))->toBe($this->site.'/storage');
});

it('preserves maintenance and blocks subsequent deployments after a migration failure', function (): void {
    $previous = installPreviousDeployment($this);
    ($this->pack)();
    $failed = runDeployment($this, ['FAIL_MIGRATE' => '1']);

    expect($failed->isSuccessful())->toBeFalse()
        ->and(realpath($this->site.'/current'))->toBe($previous)
        ->and(file_exists($this->site.'/storage/framework/down'))->toBeTrue()
        ->and(file_exists($this->site.'/deployment-recovery-required'))->toBeTrue()
        ->and(file_get_contents($this->root.'/commands'))->not->toMatch('/(?:^|\n)(?:up|.*rollback)(?:\n|$)/');
    ($this->pack)();
    expect(runDeployment($this)->getErrorOutput())->toContain('fallo anterior');
});

it('does not reopen traffic when PHP serves a different deployment identity', function (): void {
    installPreviousDeployment($this);
    ($this->pack)();
    $failed = runDeployment($this, ['WRONG_IDENTITY' => str_repeat('c', 40)]);

    expect($failed->isSuccessful())->toBeFalse()
        ->and(file_exists($this->site.'/storage/framework/down'))->toBeTrue()
        ->and(file_exists($this->site.'/deployment-recovery-required'))->toBeTrue();
});

it('refuses symbolic links in artifacts before changing persistent paths', function (): void {
    mkdir($this->root.'/outside');
    file_put_contents($this->root.'/outside/keep', 'untouched');
    symlink($this->root.'/outside', $this->source.'/bootstrap');
    ($this->pack)();
    $result = runDeployment($this);

    expect($result->isSuccessful())->toBeFalse()
        ->and(file_get_contents($this->root.'/outside/keep'))->toBe('untouched')
        ->and(file_exists($this->site.'/current'))->toBeFalse();
});

it('rejects a second deployment while the server lock is held', function (): void {
    ($this->pack)();
    $lock = fopen($this->site.'/deployment.lock', 'w');
    flock($lock, LOCK_EX);
    try {
        $result = runDeployment($this);
        expect($result->isSuccessful())->toBeFalse()->and($result->getErrorOutput())->toContain('bloqueo');
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
});

it('rejects service names that could become systemctl options', function (): void {
    ($this->pack)();
    $result = runDeployment($this, [], '--all');

    expect($result->isSuccessful())->toBeFalse()
        ->and($result->getErrorOutput())->toContain('Servicio FPM inválido')
        ->and(file_exists($this->root.'/commands'))->toBeFalse();
});

it('builds only locked source and public composition without packaging persistent data', function (): void {
    file_put_contents($this->source.'/composer.lock', '{}');
    file_put_contents($this->source.'/package-lock.json', '{}');
    file_put_contents($this->source.'/.env.example', "CMS_THEME=db-minimal\nCMS_BRAND=termocms\nCMS_MODULES=client_area\nCMS_BLOCK_PACKAGES=before_after\n");
    file_put_contents($this->source.'/.env', 'private-key-must-not-leak');
    mkdir($this->source.'/bootstrap/cache', 0700, true);
    file_put_contents($this->source.'/bootstrap/cache/config.php', 'private-cached-configuration');
    mkdir($this->source.'/storage');
    file_put_contents($this->source.'/storage/oauth-private.key', 'private');
    file_put_contents($this->root.'/bin/composer', "#!/usr/bin/env bash\nprintf '%s\\n' \"\$*\" >> \"\$TEST_LOG\"\n");
    file_put_contents($this->root.'/bin/npm', "#!/usr/bin/env bash\nprintf '%s:%s:%s\\n' \"\$*\" \"\$CMS_THEME\" \"\$CMS_MODULES\" >> \"\$TEST_LOG\"\n");
    chmod($this->root.'/bin/composer', 0755);
    chmod($this->root.'/bin/npm', 0755);
    $result = new Process(['bash', dirname(__DIR__).'/stubs/ssh/.github/scripts/build.sh', $this->artifact], $this->source, $this->environment + ['GITHUB_SHA' => $this->sha]);
    $result->run();

    expect($result->isSuccessful())->toBeTrue($result->getErrorOutput());
    $entries = (new Process(['tar', '-tzf', $this->artifact]))->mustRun()->getOutput();
    expect($entries)->toContain('composer.lock', 'package-lock.json', 'DEPLOYMENT_ID')
        ->not->toMatch('~\.env|oauth-private\.key|\./storage/|bootstrap/cache/config\.php~')
        ->and(file_get_contents($this->root.'/commands'))->toContain('install --no-dev --no-scripts', 'ci --include=dev', 'run build:db-minimal:client_area')
        ->not->toMatch('/update|private-key-must-not-leak/')
        ->and(file_get_contents($this->source.'/.env'))->toBe('private-key-must-not-leak');
});

it('never evaluates shell content from the public composition file', function (): void {
    file_put_contents($this->source.'/composer.lock', '{}');
    file_put_contents($this->source.'/package-lock.json', '{}');
    file_put_contents($this->source.'/.env.example', 'CMS_THEME=$(touch '.$this->root.'/injected)');
    $result = new Process(['bash', dirname(__DIR__).'/stubs/ssh/.github/scripts/build.sh', $this->artifact], $this->source, $this->environment + ['GITHUB_SHA' => $this->sha]);
    $result->run();

    expect($result->isSuccessful())->toBeFalse()
        ->and(file_exists($this->root.'/injected'))->toBeFalse()
        ->and(file_exists($this->root.'/commands'))->toBeFalse();
});

it('does not connect or upload when the scanned SSH host key has a different fingerprint', function (): void {
    foreach (['ssh', 'scp'] as $name) {
        file_put_contents($this->root.'/bin/'.$name, "#!/usr/bin/env bash\nprintf 'unexpected\\n' >> \"\$TEST_LOG\"\nexit 1\n");
    }
    file_put_contents($this->root.'/bin/ssh-keyscan', "#!/usr/bin/env bash\nprintf 'example.test ssh-ed25519 fixture\\n'\n");
    file_put_contents($this->root.'/bin/ssh-keygen', "#!/usr/bin/env bash\nprintf '256 SHA256:wrong fixture (ED25519)\\n'\n");
    foreach (['ssh', 'scp', 'ssh-keyscan', 'ssh-keygen'] as $name) {
        chmod($this->root.'/bin/'.$name, 0755);
    }
    ($this->pack)();
    $environment = $this->environment + ['VPS_HOST' => 'example.test', 'VPS_USER' => 'deploy', 'VPS_DIR' => '/var/www/site', 'VPS_SSH_KEY' => 'private-fixture-key', 'VPS_SSH_FINGERPRINT' => 'SHA256:expected', 'DEPLOY_URL' => 'https://example.test', 'GITHUB_SHA' => $this->sha, 'GITHUB_RUN_ID' => '1', 'GITHUB_RUN_ATTEMPT' => '1'];
    $result = new Process(['bash', dirname(__DIR__).'/stubs/ssh/.github/scripts/deploy.sh', $this->artifact], env: $environment);
    $result->run();

    expect($result->isSuccessful())->toBeFalse()
        ->and(file_exists($this->root.'/commands'))->toBeFalse()
        ->and($result->getOutput().$result->getErrorOutput())->not->toContain('private-fixture-key');
});
