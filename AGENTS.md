# Trabajar en el instalador

Installer crea aplicaciones. Core posee el asistente web y la instalación de
datos. Antes de añadir lógica, sigue `NewCommand` hasta `Selection`,
`ProjectCreator` o `ProjectFiles` y reutiliza el dueño de la operación. Mantén
los arneses en `stubs/application` y los stubs de despliegue en `stubs/ssh`.

Ejecuta PHP, Composer y pruebas mediante Docker Compose y conserva los secretos
fuera de los logs. [Mantenimiento](docs/MAINTENANCE.md) · [Convenciones de Core](https://github.com/dbravoan/dbcms-core/blob/main/docs/CONVENTIONS.md)
