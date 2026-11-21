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
            $message = sprintf('El proceso %s terminó con código %s.', basename($command[0]), $process->getExitCode() ?? 'desconocido');
            if ($command[0] === 'composer') {
                throw $this->composerFailure($command, $process->getErrorOutput()."\n".$process->getOutput(), $message);
            }
            throw new ProcessFailure($message);
        }

        return $process->getOutput();
    }

    /** @param non-empty-list<string> $command */
    private function composerFailure(array $command, string $output, string $fallback): ProcessFailure
    {
        // Composer can include repository URLs and credentials in its output. Only relay known diagnostics.
        if (preg_match('/\bcurl error (\d{1,3})\b/i', $output, $matches) === 1) {
            return new ProcessFailure('Composer encontró un error de red (cURL '.$matches[1].').');
        }
        if (preg_match('/could not be fully loaded|timed out|could not resolve host|failed to download/i', $output) === 1) {
            return new ProcessFailure('Composer no pudo consultar el repositorio o descargar un paquete.');
        }
        if (($command[1] ?? null) === 'show' && str_contains($output, 'Package "'.($command[2] ?? '').'" not found.')) {
            return new ProcessFailure($fallback, packageMissing: true);
        }
        if (preg_match('/Could not find (?:a matching version of )?package ([a-z0-9_.-]+\/[a-z0-9_.-]+)\b/i', $output, $matches) === 1) {
            return new ProcessFailure('Composer no encontró una versión disponible de '.$matches[1].' que cumpla la solicitud.');
        }
        if (preg_match('/requires (ext-[a-z0-9_-]+)[^\r\n]*it is missing from your system/i', $output, $matches) === 1) {
            return new ProcessFailure('Composer requiere la extensión de PHP '.$matches[1].'.');
        }
        if (str_contains($output, 'Your requirements could not be resolved to an installable set of packages.')) {
            return new ProcessFailure('Composer no pudo resolver las dependencias. Revisa las restricciones de versión y los requisitos de plataforma.');
        }

        return new ProcessFailure($fallback);
    }
}
