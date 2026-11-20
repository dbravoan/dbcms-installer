<?php

declare(strict_types=1);

namespace Tests;

use DbCms\Installer\ProcessRunner;

final class FakeRunner implements ProcessRunner
{
    /** @var list<array{command: non-empty-list<string>, directory: string}> */
    public array $calls = [];

    /** @var list<array<string, mixed>> */
    public array $installed = [];

    public string $composerVersion = 'Composer version 2.9.0';

    public string $nodeVersion = 'v22.21.0';

    public ?string $failureCommand = null;

    public ?\Closure $afterSetup = null;

    public function run(array $command, string $directory): string
    {
        $this->calls[] = ['command' => $command, 'directory' => $directory];
        if ($this->failureCommand !== null && in_array($this->failureCommand, $command, true)) {
            throw new \RuntimeException('Simulated error password=must-not-leak');
        }
        if ($command[0] === 'composer' && $command[1] === '--version') {
            return $this->composerVersion;
        }
        if ($command[0] === 'node') {
            return $this->nodeVersion;
        }
        if ($command[0] === 'composer' && $command[1] === 'create-project') {
            mkdir($command[3], 0700);
            file_put_contents($command[3].'/composer.json', '{}');
            file_put_contents($command[3].'/.env.example', "APP_NAME=Laravel\nAPP_KEY=\n");
            file_put_contents($command[3].'/README.md', '# Laravel');
            file_put_contents($command[3].'/.gitignore', "/.codex\n/vendor\n");
        }
        if ($command[0] === 'composer' && $command[1] === 'require' && ! in_array('--dev', $command, true)) {
            mkdir($directory.'/vendor/composer', 0700, true);
            file_put_contents($directory.'/vendor/composer/installed.json', json_encode(['packages' => $this->installed], JSON_THROW_ON_ERROR));
        }
        if (in_array('cms:setup', $command, true)) {
            file_put_contents($directory.'/.env', 'APP_KEY=private-fixture-key');
            if ($this->afterSetup !== null) {
                ($this->afterSetup)($directory);
            }
        }

        return '';
    }
}
