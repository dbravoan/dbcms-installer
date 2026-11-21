<?php

declare(strict_types=1);

namespace DbCms\Installer;

final class ProjectCreator
{
    private const LARAVEL_VERSION = 'v13.11.0';

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
        $this->preflight($parent);
        $temporary = $parent.DIRECTORY_SEPARATOR.'.dbcms-'.$name.'-'.bin2hex(random_bytes(6));
        $stage = 'crear Laravel';
        try {
            $this->runner->run(['composer', 'create-project', 'laravel/laravel', $temporary, self::LARAVEL_VERSION, '--prefer-dist', '--no-interaction', '--no-scripts'], $parent);
            $stage = 'instalar los paquetes seleccionados';
            $this->installPackages($temporary, $selection->requirements, $selection->abbreviations);
            $stage = 'resolver la composición de dbCMS';
            $composition = $this->resolvePackages($temporary, $selection);
            if (isset($composition['required_theme_package'])) {
                $stage = 'instalar el tema público predeterminado';
                $this->installPackages($temporary, [$composition['required_theme_package']]);
                $stage = 'resolver la composición de dbCMS';
                $composition = $this->resolvePackages($temporary, $selection);
                if (isset($composition['required_theme_package'])) {
                    throw new \DomainException('Core no encontró el tema público predeterminado después de instalarlo.');
                }
            }
            $stage = 'preparar la integración de dbCMS';
            $this->runner->run([
                PHP_BINARY, 'artisan', 'cms:setup',
                '--theme='.$composition['theme'], '--admin-theme='.$composition['admin_theme'],
                '--modules='.implode(',', $composition['modules']), '--blocks='.implode(',', $composition['blocks']), '--no-interaction',
            ], $temporary);
            (new ProjectFiles)->write($temporary, $name, $selection, $composition);
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
            $detail = $error instanceof ProcessFailure || $error instanceof \DomainException ? ' '.$error->getMessage() : '';
            throw new \RuntimeException("No se pudo {$stage}.{$detail} El destino final permanece sin modificar. Diagnóstico: {$temporary}", previous: $error);
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

    /** @param non-empty-list<string> $requirements
     * @param  list<string>  $abbreviations
     */
    private function installPackages(string $directory, array $requirements, array $abbreviations = []): void
    {
        try {
            $this->runner->run(['composer', 'require', ...$requirements, '--prefer-dist', '--no-interaction', '--no-scripts', '--no-ansi'], $directory);
        } catch (ProcessFailure $error) {
            foreach ($abbreviations as $name) {
                try {
                    $this->runner->run(['composer', 'show', $name, '--all', '--no-interaction', '--no-ansi'], $directory);
                } catch (ProcessFailure $lookup) {
                    if ($lookup->packageMissing) {
                        throw new \DomainException("No se encuentra el paquete {$name}.", previous: $error);
                    }
                }
            }
            throw $error;
        }
    }

    /** @return array{theme: string, admin_theme: string, modules: list<string>, blocks: list<string>}|array{required_theme_package: string} */
    private function resolvePackages(string $directory, Selection $selection): array
    {
        $this->runner->run([PHP_BINARY, 'artisan', 'package:discover', '--no-interaction'], $directory);
        $json = json_encode((object) $selection->packages, JSON_THROW_ON_ERROR);
        $output = $this->runner->run([PHP_BINARY, 'artisan', 'cms:setup', '--resolve-packages='.$json, '--no-interaction', '--no-ansi'], $directory);
        $result = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        if (is_string($result['error'] ?? null)) {
            throw new \DomainException($result['error']);
        }
        $default = $result['required_theme_package'] ?? null;
        if (is_string($default) && preg_match('~\A[a-z0-9][a-z0-9_.-]*/[a-z0-9][a-z0-9_.-]*\z~D', $default) === 1) {
            return ['required_theme_package' => $default];
        }
        $theme = $result['theme'] ?? null;
        $admin = $result['admin_theme'] ?? null;
        $modules = $result['modules'] ?? null;
        $blocks = $result['blocks'] ?? null;
        if (! self::validIdentifier($theme) || ! self::validIdentifier($admin)
            || ! is_array($modules) || ! array_is_list($modules) || ! array_all($modules, self::validIdentifier(...))
            || ! is_array($blocks) || ! array_is_list($blocks) || ! array_all($blocks, self::validIdentifier(...))) {
            throw new \DomainException('Core no devolvió una composición de paquetes válida.');
        }

        return ['theme' => $theme, 'admin_theme' => $admin, 'modules' => $modules, 'blocks' => $blocks];
    }

    /** @phpstan-assert-if-true string $value */
    private static function validIdentifier(mixed $value): bool
    {
        return is_string($value) && preg_match('/\A[a-z][a-z0-9_-]{0,63}\z/D', $value) === 1;
    }
}
