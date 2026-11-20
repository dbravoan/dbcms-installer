<?php

declare(strict_types=1);

namespace DbCms\Installer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class NewCommand extends Command
{
    public function __construct(private readonly ProjectCreator $creator = new ProjectCreator)
    {
        parent::__construct('new');
    }

    protected function configure(): void
    {
        $this->setDescription('Crea una aplicación Laravel nueva con los paquetes dbCMS seleccionados.')
            ->addArgument('directory', InputArgument::REQUIRED, 'Directorio nuevo de la aplicación')
            ->addOption('theme', null, InputOption::VALUE_REQUIRED, 'Tema público oficial', 'default-clean')
            ->addOption('admin-theme', null, InputOption::VALUE_REQUIRED, 'Apariencia admin oficial', 'dbcms')
            ->addOption('modules', null, InputOption::VALUE_REQUIRED, 'Módulos oficiales separados por comas', '')
            ->addOption('package', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Paquete adicional vendor/nombre[:restricción]')
            ->addOption('before-after', null, InputOption::VALUE_NONE, 'Instala y selecciona el bloque Before/After')
            ->addOption('sail', null, InputOption::VALUE_NONE, 'Prepara Laravel Sail con MySQL')
            ->addOption('deploy', null, InputOption::VALUE_REQUIRED, 'Workflow de GitHub Actions para SSH o none para omitirlo', Selection::DEFAULT_DEPLOYMENT);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            $theme = (string) $input->getOption('theme');
            $admin = (string) $input->getOption('admin-theme');
            $modules = self::csv((string) $input->getOption('modules'));
            if ($input->isInteractive()) {
                if (! $input->hasParameterOption('--theme')) {
                    $theme = $io->choice('Tema público', Catalog::THEMES, 'default-clean');
                }
                if (! $input->hasParameterOption('--admin-theme')) {
                    $admin = $io->choice('Apariencia del panel', Catalog::ADMIN_THEMES, 'dbcms');
                }
                if (! $input->hasParameterOption('--modules')) {
                    $choices = ['Sin módulos', ...Catalog::MODULES];
                    $modules = array_values(array_filter((array) $io->choice('Módulos opcionales (selección múltiple)', $choices, 'Sin módulos', true), static fn (string $module): bool => $module !== 'Sin módulos'));
                }
            }
            /** @var list<string> $packages */
            $packages = $input->getOption('package');
            $selection = new Selection($theme, $admin, $modules, $packages, (bool) $input->getOption('before-after'), (bool) $input->getOption('sail'), (string) $input->getOption('deploy'));
            $io->title('Crear una web con dbCMS');
            $io->listing($selection->requirements());
            $destination = $this->creator->create((string) $input->getArgument('directory'), $selection);
            $io->success('Aplicación creada en '.$destination);
            $io->text('No se ha migrado ni configurado ninguna base de datos.');
            $io->text($selection->sail ? 'Desde el proyecto: ./vendor/bin/sail up -d' : 'Desde el proyecto: php artisan serve');
            $io->text('Abre /install en la URL de tu aplicación para configurar MySQL, administrador e información legal.');
            if ($selection->deploy === 'ssh') {
                $io->text('Workflow de despliegue SSH generado en .github/workflows/deploy.yml. Sigue docs/DEPLOYMENT.md para activarlo.');
            }

            return self::SUCCESS;
        } catch (\Throwable $error) {
            $io->error($error->getMessage());

            return self::FAILURE;
        }
    }

    /** @return list<string> */
    private static function csv(string $value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode(',', $value)), static fn (string $item): bool => $item !== '')));
    }
}
