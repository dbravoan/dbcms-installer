<?php

declare(strict_types=1);

namespace DbCms\Installer;

final class ProjectFiles
{
    /** @param array{theme: string, admin_theme: string, modules: list<string>, blocks: list<string>} $composition */
    public function write(string $project, string $name, Selection $selection, array $composition): void
    {
        $example = $this->path($project, '.env.example');
        if (! is_file($example)) {
            throw new \RuntimeException('Falta el ejemplo de configuración de Laravel.');
        }
        $contents = (string) file_get_contents($example);
        $appNameAssignment = '[ \t]*(?:export[ \t]+)?APP_NAME[ \t]*=';
        if (preg_match('/^'.$appNameAssignment.'/m', $contents) === 1) {
            $legacyName = '(?:Laravel|"Laravel"|\'Laravel\'|""|\'\')?';
            $contents = preg_replace('/^('.$appNameAssignment.'[ \t]*)'.$legacyName.'(?=[ \t]*(?:#|\r?$))/m', '${1}dbCMS', $contents) ?? $contents;
        } else {
            $contents = 'APP_NAME=dbCMS'.(str_contains($contents, "\r\n") ? "\r\n" : "\n").$contents;
        }
        $contents = preg_replace('/^(?:CMS_THEME|CMS_BRAND|CMS_MODULES|CMS_BLOCK_PACKAGES)=.*\R?/m', '', $contents) ?? $contents;
        $contents = rtrim($contents)."\n\n";
        foreach (['CMS_THEME' => $composition['theme'], 'CMS_BRAND' => $composition['admin_theme'], 'CMS_MODULES' => implode(',', $composition['modules']), 'CMS_BLOCK_PACKAGES' => implode(',', $composition['blocks'])] as $key => $value) {
            $contents .= $key.'='.$value."\n";
        }
        $this->put($example, $contents);
        $root = dirname(__DIR__);
        $source = $root.'/stubs/application';
        $readme = strtr($this->stub($source.'/README.md'), [
            '{{PROJECT_NAME}}' => str_replace('_', '\\_', $name),
            '{{THEME}}' => $composition['theme'],
            '{{ADMIN_THEME}}' => $composition['admin_theme'],
            '{{MODULES}}' => $this->extensionList($composition['modules']),
            '{{BLOCKS}}' => $this->extensionList($composition['blocks']),
            '{{ENVIRONMENT}}' => $selection->sail ? 'Laravel Sail con MySQL' : 'PHP local con MySQL',
            '{{START_COMMAND}}' => $selection->sail ? './vendor/bin/sail up -d' : 'php artisan serve',
            '{{NODE_PREFIX}}' => $selection->sail ? './vendor/bin/sail ' : '',
        ]);
        if ($selection->deploy === 'ssh') {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/stubs/ssh', \FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                $relative = substr($file->getPathname(), strlen($root.'/stubs/ssh/'));
                $target = $this->path($project, $relative);
                if (file_exists($target)) {
                    throw new \RuntimeException('El proyecto ya contiene '.$relative.'.');
                }
                $this->put($target, (string) file_get_contents($file->getPathname()));
            }
            $guide = $this->path($project, 'docs/DEPLOYMENT.md');
            if (file_exists($guide)) {
                throw new \RuntimeException('El proyecto ya contiene docs/DEPLOYMENT.md.');
            }
            $this->put($guide, (string) file_get_contents($root.'/docs/DEPLOYMENT.md'));
            $routes = $this->path($project, 'routes/web.php');
            $existing = is_file($routes) ? (string) file_get_contents($routes) : "<?php\n";
            $this->put($routes, rtrim($existing)."\n\n\\Illuminate\\Support\\Facades\\Route::get('/_deployment/health', \\App\\Http\\Controllers\\DeploymentHealthController::class)->withoutMiddleware('web');\n");
            $readme .= "\n## Despliegue\n\nEl flujo SSH se habilita al configurar GitHub y el servidor según la [guía de despliegue](docs/DEPLOYMENT.md).\n";
        }
        $this->writeAgentFiles($project, $source);
        $this->allowProjectCodexAgents($project);
        $this->put($this->path($project, 'README.md'), $readme);
    }

    private function allowProjectCodexAgents(string $project): void
    {
        $path = $this->path($project, '.gitignore');
        if (! is_file($path)) {
            return;
        }
        $original = $this->stub($path);
        $eol = str_contains($original, "\r\n") ? "\r\n" : "\n";
        $pattern = '~^/\.codex/?(?=\r?$)~m';
        $contents = preg_replace($pattern, '/.codex/*'.$eol.'!/.codex/agents/', $original);
        if ($contents !== null && $contents !== $original) {
            $this->put($path, $contents);
        }
    }

    private function writeAgentFiles(string $project, string $source): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($source) + 1));
            if ($relative === 'README.md') {
                continue;
            }
            $target = $this->path($project, $relative);
            $contents = $this->stub($file->getPathname());
            if (! in_array($relative, ['AGENTS.md', 'CLAUDE.md'], true) && file_exists($target) && (! is_file($target) || file_get_contents($target) !== $contents)) {
                throw new \RuntimeException('El proyecto ya contiene '.$relative.'.');
            }
            $this->put($target, $contents);
        }
    }

    private function stub(string $path): string
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException('No se pudo leer el archivo de integración: '.basename($path).'.');
        }

        return $contents;
    }

    /** @param list<string> $identifiers */
    private function extensionList(array $identifiers): string
    {
        return $identifiers === [] ? 'Ninguno adicional' : '`'.implode('`, `', $identifiers).'`';
    }

    private function path(string $project, string $relative): string
    {
        if (is_link($project)) {
            throw new \RuntimeException('El proyecto no puede ser un enlace simbólico.');
        }
        $path = $project;
        foreach (explode('/', $relative) as $part) {
            $path .= '/'.$part;
            if (is_link($path)) {
                throw new \RuntimeException('No se escriben archivos de proyecto a través de enlaces simbólicos.');
            }
        }

        return $path;
    }

    private function put(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('No se pudo preparar un directorio del proyecto.');
        }
        if (file_put_contents($path, $contents) === false) {
            throw new \RuntimeException('No se pudo escribir un archivo del proyecto.');
        }
    }
}
