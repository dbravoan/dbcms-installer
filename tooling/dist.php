<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Process\Process;

$root = dirname(__DIR__);
$directory = $root.'/.build/dist';
if (! is_dir($directory)) {
    mkdir($directory, 0700, true);
}
$archive = new Process(['composer', 'archive', '--format=zip', '--dir='.$directory, '--file=installer'], $root);
$archive->mustRun();
$zip = new ZipArchive;
if ($zip->open($directory.'/installer.zip') !== true) {
    throw new RuntimeException('No se pudo abrir el distribuible.');
}
for ($index = 0; $index < $zip->numFiles; $index++) {
    $name = $zip->getNameIndex($index);
    if ($name === false || preg_match('~(?:^|/)(?:\.env(?:\.[^/]*)?|vendor|tests|\.git|\.build)(?:/|$)~', $name)) {
        throw new RuntimeException('El distribuible contiene archivos privados o de desarrollo.');
    }
}
$gitArchive = $directory.'/installer-git.zip';
(new Process(['git', 'archive', '--worktree-attributes', '--format=zip', '--output='.$gitArchive, 'HEAD'], $root))->mustRun();
$published = new ZipArchive;
if ($published->open($gitArchive) !== true) {
    throw new RuntimeException('No se pudo abrir el archivo Git distribuible.');
}
for ($index = 0; $index < $published->numFiles; $index++) {
    $name = $published->getNameIndex($index);
    if (is_string($name) && is_file($root.'/'.$name) && $zip->locateName($name) === false) {
        throw new RuntimeException('Git distribuye un archivo excluido por Composer: '.$name);
    }
}
$published->close();
$app = $directory.'/isolated-'.bin2hex(random_bytes(4));
mkdir($app, 0700);
$zip->extractTo($app);
$zip->close();
(new Process(['composer', 'install', '--no-dev', '--no-interaction', '--prefer-dist'], $app))->mustRun();
$result = (new Process([PHP_BINARY, 'bin/dbcms', 'new', '--help'], $app))->mustRun();
if (! str_contains($result->getOutput(), '--package') || ! str_contains($result->getOutput(), '--deploy')) {
    throw new RuntimeException('El ejecutable distribuido no ofrece la instalación de extensiones.');
}
$probe = <<<'PHP'
require 'vendor/autoload.php';
$writer = new \DbCms\Installer\ProjectFiles;
foreach ([[false, 'none'], [false, 'ssh'], [true, 'none'], [true, 'ssh']] as [$sail, $deploy]) {
    $project = $sail ? 'sail-'.$deploy : 'local-'.$deploy;
    mkdir($project, 0700);
    file_put_contents($project.'/.env.example', "APP_KEY=\n");
    $modules = $deploy === 'ssh' ? ['client_area'] : [];
    $blocks = $deploy === 'ssh' ? ['external_blocks'] : [];
    $writer->write(getcwd().'/'.$project, $project, new \DbCms\Installer\Selection(sail: $sail, deploy: $deploy), [
        'theme' => 'default-clean', 'admin_theme' => 'dbcms', 'modules' => $modules, 'blocks' => $blocks,
    ]);
    $readme = file_get_contents($project.'/README.md');
    foreach (['# '.$project, 'dbcms:site:pending', 'storage/installed', 'CMS_INSTALL_TOKEN', $sail ? './vendor/bin/sail npm ci' : "\nnpm ci\n", $sail ? 'composer:2 install' : 'php artisan key:generate', $deploy === 'ssh' ? 'external_blocks' : 'Ninguno adicional'] as $needle) {
        if (!str_contains($readme, $needle)) { throw new RuntimeException('Missing documentation in '.$project.': '.$needle); }
    }
    if (!str_contains(file_get_contents($project.'/AGENTS.md'), 'Contratos de extensión') || trim(file_get_contents($project.'/CLAUDE.md')) !== '@AGENTS.md') {
        throw new RuntimeException('Project agent instructions are incomplete in '.$project.'.');
    }
    foreach (['README.md', 'AGENTS.md', 'CLAUDE.md', 'GEMINI.md', '.agents', '.opencode', '.gemini', '.codex'] as $path) {
        if (!file_exists($project.'/'.$path)) { throw new RuntimeException('Missing distributed agent files: '.$project.'/'.$path); }
    }
    $templates = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('stubs/application', FilesystemIterator::SKIP_DOTS));
    foreach ($templates as $file) {
        $relative = substr($file->getPathname(), strlen('stubs/application/'));
        if ($relative !== 'README.md' && file_get_contents($project.'/'.$relative) !== file_get_contents($file->getPathname())) {
            throw new RuntimeException('Project agent file does not match the distributed template: '.$relative);
        }
    }
    if (str_contains($readme, 'docs/DEPLOYMENT.md') !== ($deploy === 'ssh')) {
        throw new RuntimeException('Deployment documentation does not match the selection.');
    }
}
foreach (['.github/workflows/deploy.yml', '.github/scripts/build.sh', '.github/scripts/deploy.sh', '.github/scripts/deploy-remote.sh', 'docs/DEPLOYMENT.md', 'app/Http/Controllers/DeploymentHealthController.php'] as $file) {
    if (!is_file('local-ssh/'.$file)) { throw new RuntimeException('Missing distributed template: '.$file); }
}
PHP;
(new Process([PHP_BINARY, '-r', $probe], $app))->mustRun();
foreach (['build.sh', 'deploy.sh', 'deploy-remote.sh'] as $script) {
    (new Process(['bash', '-n', $app.'/local-ssh/.github/scripts/'.$script]))->mustRun();
}
fwrite(STDOUT, "Distribuible instalado y ejecutado sin el checkout original.\n");
