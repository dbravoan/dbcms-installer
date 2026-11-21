# Desarrollo, pruebas y publicación

## Arquitectura del instalador

| Pieza | Responsabilidad |
| --- | --- |
| `NewCommand` | Lee las opciones y muestra ejemplos de uso. |
| `Selection` | Normaliza las listas, amplía las abreviaturas por tipo y reúne los requisitos de Composer. |
| `ProjectCreator` y `ProcessRunner` | Ejecutan Composer, `cms:setup` y Vite en un directorio temporal. El destino se publica tras completar esas fases. Las pruebas controlan los procesos mediante `FakeRunner`. |
| `ProjectFiles` | Genera los README, arneses y archivos de despliegue a partir de `stubs/`. |

Reutiliza el dueño de la operación y sus validaciones al añadir una opción o
un archivo. Core integra la aplicación con `cms:setup` y posee el asistente y
la instalación de datos. [Arquitectura de Core](https://github.com/dbravoan/dbcms-core/blob/main/docs/CORE_AND_EXTENSIONS.md)

## Comprobaciones

PHP y Composer se ejecutan en el contenedor del repositorio:

```bash
docker compose run --build --rm -T tools composer install --no-interaction
docker compose run --rm -T tools composer check
docker compose run --rm -T tools composer test
docker compose run --rm -T tools composer test:dist
docker compose down --volumes --remove-orphans
```

Pest cubre la selección, compatibilidad declarada, protección de destinos, fallos
de procesos y los argumentos enviados a core. Pint y PHPStan nivel 8 comprueban
el código. El smoke instala el archivo distribuible y ejecuta su CLI sin el
checkout original.

Las pruebas de los scripts SSH usan procesos simulados y archivos desechables;
comprueban primera instalación, actualización, bloqueos, secretos y recuperación.
El contrato de la web generada está en [despliegue](DEPLOYMENT.md).

Antes de publicar hay que ejecutar también una creación real contra los paquetes
públicos: composición mínima, módulos seleccionados y un paquete de otro vendor,
seguida de la configuración web en una base MySQL desechable. Los tests con
procesos simulados no sustituyen esa comprobación.

## Dependencias

La línea `0.2` utiliza Core `~0.0.2`, PHP 8.4, Laravel 13 y Node 22.12 o posterior. El esqueleto
Laravel está fijado en `ProjectCreator::LARAVEL_VERSION`. Cambiarlo requiere comprobar
el resultado real de `cms:setup`, Vite, el asistente y Sail. Composer conserva el
lockfile de la aplicación generada.

Composer resuelve dependencias. Antes de preparar la aplicación, `cms:setup`
resuelve la composición desde los metadatos instalados y comprueba el tipo de
cada paquete indicado en una opción por tipo.

## Publicar

1. Integrar el candidato en `main` y esperar CI correcta para su SHA exacto.
2. Actualizar el changelog y comprobar el distribuible y la creación real.
3. Crear y subir un tag anotado nuevo para el SHA comprobado.
4. Esperar la indexación de Packagist y probar una instalación exacta del tag
   desde un Composer home vacío.

Cada repositorio publica sus propios tags inmutables.
