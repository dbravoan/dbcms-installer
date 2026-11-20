<?php

declare(strict_types=1);

namespace DbCms\Installer;

use Symfony\Component\Process\Process;

final class SymfonyProcessRunner implements ProcessRunner
{
    public function run(array $command, string $directory): string
    {
        $process = new Process($command, $directory, timeout: 1800);
        $process->run();
        if (! $process->isSuccessful()) {
            throw new \RuntimeException(sprintf('El proceso %s terminó con código %s.', basename($command[0]), $process->getExitCode() ?? 'desconocido'));
        }

        return $process->getOutput();
    }
}
