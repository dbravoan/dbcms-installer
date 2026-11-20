<?php

declare(strict_types=1);

namespace DbCms\Installer;

final readonly class Selection
{
    private const OFFICIAL_PACKAGE_CONSTRAINT = '~0.0.1';

    public const DEFAULT_DEPLOYMENT = 'ssh';

    /** @param list<string> $modules
     * @param  list<string>  $additionalPackages
     */
    public function __construct(
        public string $theme = 'default-clean',
        public string $adminTheme = 'dbcms',
        public array $modules = [],
        public array $additionalPackages = [],
        public bool $beforeAfter = false,
        public bool $sail = false,
        public string $deploy = self::DEFAULT_DEPLOYMENT,
    ) {
        if (! in_array($deploy, ['none', 'ssh'], true)) {
            throw new \InvalidArgumentException('El despliegue admite --deploy=none o --deploy=ssh.');
        }
        foreach ([$theme, $adminTheme] as $identifier) {
            if (preg_match('/\A[a-z][a-z0-9_-]*\z/D', $identifier) !== 1) {
                throw new \InvalidArgumentException('El identificador del tema no es válido.');
            }
        }
        if ((! in_array($theme, Catalog::THEMES, true) || ! in_array($adminTheme, Catalog::ADMIN_THEMES, true)) && $additionalPackages === []) {
            throw new \InvalidArgumentException('Selecciona el paquete de tu tema con --package=vendor/paquete.');
        }
        foreach ($modules as $module) {
            if (! in_array($module, Catalog::MODULES, true)) {
                throw new \InvalidArgumentException("Módulo oficial desconocido: {$module}. Usa --package para otro vendor.");
            }
        }
        foreach ($additionalPackages as $package) {
            self::parsePackage($package);
        }
    }

    /** @return list<string> */
    public function requirements(): array
    {
        $requirements = ['dbravoan/dbcms-core' => self::OFFICIAL_PACKAGE_CONSTRAINT];
        if (in_array($this->theme, Catalog::THEMES, true)) {
            $requirements['dbravoan/dbcms-theme-'.$this->theme] = self::OFFICIAL_PACKAGE_CONSTRAINT;
        }
        if ($this->adminTheme !== 'dbcms' && in_array($this->adminTheme, Catalog::ADMIN_THEMES, true)) {
            $requirements['dbravoan/dbcms-admin-theme-'.$this->adminTheme] = self::OFFICIAL_PACKAGE_CONSTRAINT;
        }
        foreach (array_unique($this->modules) as $module) {
            $requirements['dbravoan/dbcms-module-'.$module] = self::OFFICIAL_PACKAGE_CONSTRAINT;
        }
        if ($this->beforeAfter) {
            $requirements['dbravoan/dbcms-blocks-before-after'] = self::OFFICIAL_PACKAGE_CONSTRAINT;
        }
        foreach ($this->additionalPackages as $package) {
            [$name, $constraint] = self::parsePackage($package);
            if (array_key_exists($name, $requirements) && $requirements[$name] !== $constraint) {
                throw new \InvalidArgumentException("El paquete {$name} ya forma parte de la selección con otra restricción.");
            }
            $requirements[$name] = $constraint;
        }

        return array_map(static fn (string $name, ?string $constraint): string => $name.($constraint === null ? '' : ':'.$constraint), array_keys($requirements), array_values($requirements));
    }

    /** @return array{string, string|null} */
    private static function parsePackage(string $package): array
    {
        if (preg_match('/\A([a-z0-9](?:[a-z0-9_.-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9_.-]*[a-z0-9])?)(?::([^\r\n]+))?\z/D', $package, $matches) !== 1) {
            throw new \InvalidArgumentException('Usa --package=vendor/paquete o --package=vendor/paquete:restricción.');
        }

        return [$matches[1], $matches[2] ?? null];
    }
}
