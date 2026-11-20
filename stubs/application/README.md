# {{PROJECT_NAME}}

Aplicación Laravel que utiliza dbCMS para gestionar el sitio. Este repositorio contiene el código y las personalizaciones de la web.

<!-- dbcms:site:pending -->
El asistente completa aquí la identidad del sitio.
<!-- /dbcms:site -->

## Composición inicial

| Componente | Selección |
| --- | --- |
| Tema público | `{{THEME}}` |
| Apariencia del panel | `{{ADMIN_THEME}}` |
| Módulos | {{MODULES}} |
| Paquetes de bloques | {{BLOCKS}} |
| Entorno de desarrollo | {{ENVIRONMENT}} |

Consulta `composer.lock` para conocer las versiones de los paquetes y `.env.example` para ver la selección inicial. En una instalación configurada, el panel muestra los módulos activos y la configuración del entorno determina la URL y la ruta de acceso.

## Primer arranque

`dbcms new` prepara la aplicación, instala las dependencias y compila los recursos. Arranca el entorno desde este directorio.

```bash
{{START_COMMAND}}
```

Abre `/install` en la dirección del servidor. Usa en el navegador el código de un solo uso `CMS_INSTALL_TOKEN` de tu `.env`. El asistente conecta MySQL, prepara las tablas y registra el sitio, el administrador y los datos legales. Su confirmación enlaza la portada y el panel de ese entorno.

[Recorrido del asistente con capturas](https://github.com/dbravoan/dbcms-installer/blob/main/docs/HOW_TO_START.md) · [Recuperar el acceso al asistente](https://github.com/dbravoan/dbcms-installer#recupera-el-acceso-al-asistente)

## Trabajar desde otro checkout

Prepara el `.env` del entorno a partir de `.env.example` y ejecuta `composer install`. Con Sail, arranca el entorno con el comando anterior antes de ejecutar npm.

```bash
{{NODE_PREFIX}}npm ci
{{NODE_PREFIX}}npm run build
```

Versiona `composer.json`, `composer.lock`, `package.json`, `package-lock.json` y `.env.example`. Mantén el `.env`, las claves y los datos de `storage` en el entorno correspondiente. Si la web es nueva y no tiene código de instalación, genera uno con `openssl rand -hex 32` y guárdalo como `CMS_INSTALL_TOKEN` en su `.env`. Para recuperar una web existente, utiliza sus claves, archivos y base de datos.

## Dónde hacer cambios

- `app/`, `routes/` y `resources/` contienen el código, las rutas y las personalizaciones de esta web. Consulta el contrato de extensiones para registrar vistas Blade y personalizar los recursos que compila Vite.
- Core proporciona páginas, secciones, permisos, idiomas, mercados y MCP. Los módulos aportan funciones como Blog. Los temas controlan la presentación pública o la apariencia del panel. Actualiza estos paquetes mediante Composer.
- Para añadir una extensión, instala su paquete, recompila los recursos y sigue su procedimiento de activación. [Añadir módulos](https://github.com/dbravoan/dbcms-installer#añadir-módulos-a-una-web-existente)
- Antes de implementar, busca un servicio, bloque o componente equivalente en los paquetes instalados y amplíalo en su propietario si hace falta. [Convenciones de Core](https://github.com/dbravoan/dbcms-core/blob/main/docs/CONVENTIONS.md)

[Arquitectura de Core](https://github.com/dbravoan/dbcms-core/blob/main/docs/CORE_AND_EXTENSIONS.md) · [Extensiones, Blade y recursos](https://github.com/dbravoan/dbcms-core/blob/main/docs/EXTENSIONS.md)

## Trabajar con asistentes

Usa el arnés del proyecto para desarrollar módulos y bloques, personalizar temas y maquetar composiciones. Sus guías sirven a personas y asistentes. [Arneses del proyecto](AGENTS.md#arneses-del-proyecto)
