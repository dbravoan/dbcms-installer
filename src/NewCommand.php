<?php

declare(strict_types=1);

namespace DbCms\Installer;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class NewCommand extends Command
{
    private const PACKAGE_OPTIONS = [
        'theme' => 'Temas públicos. El primero queda activo',
        'admin-theme' => 'Temas de panel. El primero queda activo',
        'module' => 'Módulos',
        'block-package' => 'Paquetes de bloques',
        'package' => 'Paquetes adicionales con nombre completo vendor/paquete',
    ];

    public function __construct(private readonly ProjectCreator $creator = new ProjectCreator)
    {
        parent::__construct('new');
    }

    protected function configure(): void
    {
        $this->setDescription('Crea una aplicación Laravel nueva con los paquetes dbCMS seleccionados.')
            ->setHelp(<<<'HELP'
                Separa los paquetes por comas o repite la opción. Cada entrada acepta una restricción tras ':'.
                Las opciones por tipo admiten nombres completos y abreviaturas de los paquetes de dbravoan.

                  dbcms new mi-web --theme=db-cyber,db-editorial --admin-theme=termocms --module=blog,gallery
                  dbcms new mi-web --block-package=before-after,scrollytelling --package='acme/agenda:^1,acme/mapas' --sail
                  dbcms new mi-web --module='blog:>=0.0.1 <0.1,gallery'
                HELP)
            ->addArgument('directory', InputArgument::REQUIRED, 'Directorio nuevo de la aplicación')
            ->addOption('sail', null, InputOption::VALUE_NONE, 'Prepara Laravel Sail con MySQL')
            ->addOption('deploy', null, InputOption::VALUE_REQUIRED, 'Workflow de GitHub Actions para SSH o none para omitirlo', Selection::DEFAULT_DEPLOYMENT);
        foreach (self::PACKAGE_OPTIONS as $option => $description) {
            $this->addOption($option, null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, $description);
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        try {
            /** @var array<string, list<string>> $options */
            $options = array_intersect_key($input->getOptions(), self::PACKAGE_OPTIONS);
            $selection = new Selection($options, (bool) $input->getOption('sail'), (string) $input->getOption('deploy'));
            $io->title('Crear una web con dbCMS');
            $io->listing(array_map(OutputFormatter::escape(...), $selection->requirements));
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
            $io->error(OutputFormatter::escape($error->getMessage()));

            return self::FAILURE;
        }
    }
}
