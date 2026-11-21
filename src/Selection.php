<?php

declare(strict_types=1);

namespace DbCms\Installer;

final readonly class Selection
{
    private const PREFIXES = [
        'theme' => 'dbravoan/dbcms-theme-',
        'admin-theme' => 'dbravoan/dbcms-admin-theme-',
        'module' => 'dbravoan/dbcms-module-',
        'block-package' => 'dbravoan/dbcms-blocks-',
    ];

    private const PACKAGE_NAME = '[a-z0-9](?:[a-z0-9_.-]*[a-z0-9])?';

    public const DEFAULT_DEPLOYMENT = 'ssh';

    /** @var array<string, list<string>> */
    public array $packages;

    /** @var list<string> */
    public array $abbreviations;

    /** @var non-empty-list<string> */
    public array $requirements;

    /** @param array<string, list<string>> $options */
    public function __construct(
        array $options = [],
        public bool $sail = false,
        public string $deploy = self::DEFAULT_DEPLOYMENT,
    ) {
        if (! in_array($deploy, ['none', 'ssh'], true)) {
            throw new \InvalidArgumentException('El despliegue admite --deploy=none o --deploy=ssh.');
        }

        $packages = $abbreviations = [];
        $requirements = ['dbravoan/dbcms-core' => '~0.0.2'];
        foreach ($options as $option => $values) {
            foreach ($values as $value) {
                foreach (explode(',', $value) as $entry) {
                    [$name, $constraint] = self::parsePackage($entry, $option);
                    if (! str_contains($name, '/')) {
                        $name = self::PREFIXES[$option].$name;
                        $abbreviations[$name] = $name;
                    }
                    $packages[$option][$name] = $name;
                    $previous = $requirements[$name] ?? null;
                    if ($previous !== null && $constraint !== null && $previous !== $constraint) {
                        throw new \InvalidArgumentException("El paquete {$name} ya forma parte de la selección con otra restricción.");
                    }
                    $requirements[$name] = $constraint ?? $previous;
                }
            }
        }

        $this->packages = array_map(array_values(...), $packages);
        $this->abbreviations = array_values($abbreviations);
        $this->requirements = array_map(static fn (string $name, ?string $constraint): string => $name.($constraint === null ? '' : ':'.$constraint), array_keys($requirements), array_values($requirements));
    }

    /** @return array{string, string|null} */
    private static function parsePackage(string $entry, string $option): array
    {
        $parts = explode(':', trim($entry), 2);
        $name = $parts[0];
        $constraint = isset($parts[1]) ? trim($parts[1]) : null;
        $segment = self::PACKAGE_NAME;
        $pattern = isset(self::PREFIXES[$option]) ? "(?:{$segment}/)?{$segment}" : "{$segment}/{$segment}";
        if (preg_match("~\\A{$pattern}\\z~D", $name) !== 1 || ($constraint !== null && ($constraint === '' || preg_match('/[[:cntrl:]]/', $constraint) === 1))) {
            $syntax = isset(self::PREFIXES[$option]) ? 'nombre o vendor/paquete' : 'vendor/paquete';
            throw new \InvalidArgumentException("Cada entrada de --{$option} debe ser {$syntax}, con una restricción opcional después de ':'.");
        }

        return [$name, $constraint];
    }
}
