# Changelog

## 0.2.0

- `--theme`, `--admin-theme`, `--module`, `--block-package` y `--package` aceptan listas separadas por comas y opciones repetidas. Los temas del primer elemento de cada lista quedan activos.
- Composer resuelve todos los paquetes. Core valida sus tipos y elige la composición inicial. `--module` y `--block-package` sustituyen `--modules` y `--before-after`.

## 0.0.1

- Distribución por GitHub y Composer con stubs de despliegue.

- Creación de aplicaciones Laravel con core y paquetes Composer seleccionados.
- Selección de módulos, temas, Before/After, Sail y extensiones de cualquier vendor.
- Preparación por CLI y configuración por asistente web.
- Creación temporal, protección de destinos y comprobación del distribuible.
