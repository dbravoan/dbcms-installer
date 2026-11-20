<?php

declare(strict_types=1);

namespace DbCms\Installer;

final class ProjectCreator
{
    public function __construct(private readonly ProcessRunner $runner = new SymfonyProcessRunner) {}

    public function create(string $destination, Selection $selection, ?string $workingDirectory = null): string
    {
        $workingDirectory ??= getcwd() ?: throw new \RuntimeException('No se pudo determinar el directorio de trabajo.');
        $destination = str_starts_with($destination, DIRECTORY_SEPARATOR) ? $destination : $workingDirectory.DIRECTORY_SEPARATOR.$destination;
        $parent = realpath(dirname($destination));
        $name = basename($destination);
        if ($parent === false || ! is_dir($parent) || ! is_writable($parent) || preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]*\z/D', $name) !== 1) {
            throw new \InvalidArgumentException('El destino necesita un nombre válido y un directorio padre existente y escribible.');
        }
        $destination = $parent.DIRECTORY_SEPARATOR.$name;
        $this->assertNewDestination($destination);
        $requirements = $selection->requirements();
        $this->preflight($parent);
        $temporary = $parent.DIRECTORY_SEPARATOR.'.dbcms-'.$name.'-'.bin2hex(random_bytes(6));
        $stage = 'crear Laravel';
        try {
            $this->runner->run(['composer', 'create-project', 'laravel/laravel', $temporary, Catalog::LARAVEL_VERSION, '--prefer-dist', '--no-interaction', '--no-scripts'], $parent);
            $stage = 'instalar los paquetes seleccionados';
            $this->runner->run(['composer', 'require', ...$requirements, '--prefer-dist', '--no-interaction', '--no-scripts'], $temporary);
            $stage = 'descubrir los proveedores';
            $this->runner->run([PHP_BINARY, 'artisan', 'package:discover', '--no-interaction'], $temporary);
            [$modules, $blocks] = $this->contributions($temporary);
            $stage = 'preparar la integración de dbCMS';
            $this->runner->run([PHP_BINARY, 'artisan', 'cms:setup', '--theme='.$selection->theme, '--admin-theme='.$selection->adminTheme, '--modules='.implode(',', $modules), '--blocks='.implode(',', $blocks), '--no-interaction'], $temporary);
            (new ProjectFiles)->write($temporary, $name, $selection, $modules, $blocks);
            $this->runner->run(['composer', 'dump-autoload', '--no-scripts', '--no-interaction'], $temporary);
            if ($selection->sail) {
                $stage = 'preparar Laravel Sail';
                $this->runner->run(['composer', 'require', '--dev', 'laravel/sail:^1.67', '--no-interaction'], $temporary);
                $this->runner->run([PHP_BINARY, 'artisan', 'sail:install', '--with=mysql', '--no-interaction'], $temporary);
            }
            $stage = 'instalar las dependencias frontend';
            $this->runner->run(['npm', 'install', '--no-audit', '--no-fund'], $temporary);
            $stage = 'compilar los recursos';
            $this->runner->run(['npm', 'run', 'build'], $temporary);
            $stage = 'activar el directorio final';
            $this->assertNewDestination($destination);
            if (! rename($temporary, $destination)) {
                throw new \RuntimeException('El destino ha cambiado o no pudo crearse.');
            }

            return $destination;
        } catch (\Throwable $error) {
            if (is_dir($temporary)) {
                file_put_contents($temporary.'/.dbcms-install-failed.json', json_encode(['stage' => $stage, 'time' => gmdate(DATE_ATOM)], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)."\n");
                @chmod($temporary.'/.dbcms-install-failed.json', 0600);
            }
            throw new \RuntimeException("No se pudo {$stage}. El destino final permanece sin modificar. Diagnóstico: {$temporary}", previous: $error);
        }
    }

    private function preflight(string $directory): void
    {
        $composer = $this->runner->run(['composer', '--version', '--no-ansi'], $directory);
        if (preg_match('/Composer (?:version )?2\./i', $composer) !== 1) {
            throw new \RuntimeException('Se necesita Composer 2.');
        }
        $node = trim($this->runner->run(['node', '--version'], $directory));
        if (preg_match('/\Av?(\d+\.\d+\.\d+)\z/D', $node, $matches) !== 1 || version_compare($matches[1], '22.12.0', '<')) {
            throw new \RuntimeException('Se necesita Node.js 22.12 o posterior.');
        }
        $this->runner->run(['npm', '--version'], $directory);
    }

    private function assertNewDestination(string $destination): void
    {
        clearstatcache(true, $destination);
        if (file_exists($destination) || is_link($destination)) {
            throw new \InvalidArgumentException('El destino ya existe. El instalador solo crea proyectos nuevos.');
        }
    }

    /** @return array{list<string>, list<string>} */
    private function contributions(string $directory): array
    {
        $file = $directory.'/vendor/composer/installed.json';
        $data = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($data) || ! is_array($data['packages'] ?? null)) {
            throw new \RuntimeException('Composer no produjo un inventario de paquetes válido.');
        }
        $modules = $blocks = [];
        foreach ($data['packages'] as $package) {
            if (! is_array($package)) {
                continue;
            }
            $type = $package['type'] ?? null;
            $identifier = $package['extra']['dbcms']['identifier'] ?? null;
            if (! in_array($type, ['dbcms-module', 'dbcms-block-package'], true)) {
                continue;
            }
            if (! is_string($identifier) || preg_match('/\A[a-z][a-z0-9_-]*\z/D', $identifier) !== 1) {
                throw new \RuntimeException('Una extensión tiene un identificador dbCMS inválido.');
            }
            if ($type === 'dbcms-module') {
                $modules[] = $identifier;
            } else {
                $blocks[] = $identifier;
            }
        }

        return [array_values(array_unique($modules)), array_values(array_unique($blocks))];
    }
}
