# Instalador de dbCMS

Creado por [David Bravo](https://davidbravo.com.es) con amor e inteligencia artificial.

`dbcms new` crea una aplicación Laravel en un directorio nuevo, descarga Core y
los paquetes elegidos con Composer, prepara dbCMS mediante `cms:setup` y compila
los recursos. El asistente `/install` se abre con un código de un solo uso.
Después conecta MySQL, ejecuta las migraciones, instala y activa los módulos
seleccionados y crea la cuenta administradora.

- [Crear y arrancar una web](#crear-una-web)
- [Instalación paso a paso de David Bravo](docs/HOW_TO_START.md)
- [Conexión MCP y creación de un blog de ejemplo](docs/HOW_TO_MCP.md)
- [Creación de un módulo de registro y juegos privados](docs/HOW_TO_BUILD_CLIENT_AREA.md)
- [Creación de una portada animada con noticias mediante MCP](docs/HOW_TO_BUILD_HOMEPAGE_MCP.md)
- [Despliegue SSH de la web](docs/DEPLOYMENT.md)
- [Añadir módulos a una web existente](#añadir-módulos-a-una-web-existente)
- [Crear un módulo propio](#crear-un-módulo-propio)
- [Arquitectura, pruebas y publicación del instalador](docs/MAINTENANCE.md)

## Crear una web

Elige [Sail y contenedores](#sail-y-contenedores) si partes de un equipo con
Docker. Si ya tienes PHP, Composer y Node, puedes usar la
[instalación local](#instalación-local). Ambas rutas crean Laravel, instalan
los paquetes que elijas y compilan los recursos antes de abrir `/install`.

### Instalación local

Necesitas PHP 8.4 con las extensiones que exige Core, Composer 2 y Node.js 22.12
o posterior con npm. Composer comprueba extensiones como `pdo_mysql`, `dom`,
`fileinfo`, `gd`, `intl` y `curl` al resolver Core. Para el asistente, prepara una
base MySQL vacía y un usuario con permisos para crear sus tablas.

```bash
composer global require 'dbravoan/dbcms-installer:~0.2.0'
export PATH="$(composer global config bin-dir --absolute):$PATH"
dbcms new mi-web
cd mi-web
php artisan serve
```

Ejecuta `dbcms new` en un directorio escribible y elige un nombre de destino
que aún no exista. Guarda el directorio de binarios de Composer en el `PATH`
de tu shell para usar `dbcms` en futuras sesiones. Con el servidor en ejecución,
abre `/install` en la dirección que muestra `php artisan serve`, normalmente
`http://127.0.0.1:8000/install`.

### Sail y contenedores

Necesitas Git, un shell compatible con Bash y Docker con Compose en ejecución.
En Windows, usa WSL2 con la integración de Docker. Clona el repositorio de
Installer para obtener su entorno `tools`. Dentro del contenedor se ejecutan
PHP, Composer y Node durante la creación de la web. [Laravel Sail](https://laravel.com/framework/docs/sail)

```bash
git clone --branch v0.2.1 --depth 1 https://github.com/dbravoan/dbcms-installer.git
cd dbcms-installer
docker compose build tools
docker compose run --rm -T --user "$(id -u):$(id -g)" -e HOME=/tmp \
  tools composer install --no-dev --no-interaction
mkdir -p ../sitios
docker compose run --rm -T --user "$(id -u):$(id -g)" -e HOME=/tmp \
  -v "$(cd ../sitios && pwd):/sites" tools php bin/dbcms new /sites/mi-web --sail
cd ../sitios/mi-web
./vendor/bin/sail up -d
```

`--sail` prepara la configuración. `sail up` arranca los servicios de Laravel
y MySQL. Si ya tienes el CLI de Composer, también puedes generar ese entorno
con `dbcms new mi-web --sail` desde tu instalación local.

Abre `http://localhost/install`. Si el puerto HTTP está ocupado, configura
`APP_PORT` en `.env` antes de arrancar Sail y úsalo en la URL. En el asistente,
usa `mysql` como host, `3306` como puerto interno y los valores de `DB_DATABASE`,
`DB_USERNAME` y `DB_PASSWORD` que Sail ha preparado en `.env`.

### Completa el asistente web

Por defecto se usa el tema público `default-clean` y la apariencia de panel
integrada `dbcms`. Consulta cómo [seleccionar paquetes](#seleccionar-paquetes)
y los pasos de arranque y recuperación del README generado en tu web.

1. **Acceso.** Abre `/install`, introduce el valor de `CMS_INSTALL_TOKEN` del
   `.env` de la web y pulsa **Abrir el asistente**. `cms:setup` genera el código
   al crear la aplicación. Cada código autoriza una sesión del navegador. Usa
   HTTPS si accedes en remoto y guarda el código fuera de Git.
2. **Requisitos.** Revisa las comprobaciones de PHP y los permisos de escritura.
3. **Base de datos.** Introduce el host, puerto, nombre de la base, usuario y
   contraseña. Si MySQL está en el mismo equipo, usa `127.0.0.1` y su puerto
   (normalmente `3306`). Pulsa **Probar conexión** y luego **Guardar y continuar**.
   Si la base aún no existe, la opción de crearla requiere permiso en MySQL.
4. **Aprovisionamiento.** Selecciona **Instalación limpia** y pulsa **Iniciar
   aprovisionamiento**. Core prepara las tablas, instala y activa los módulos
   elegidos y habilita los paquetes de bloques seleccionados. Si el asistente
   vuelve al formulario de acceso, [recupera el acceso](#recupera-el-acceso-al-asistente)
   antes de continuar.
5. **Sitio, administrador, titular y privacidad.** Indica el nombre y la URL que
   usas para abrir la web, elige la ruta del panel y crea la cuenta administradora.
   Completa los datos del titular, el alojamiento y los tratamientos requeridos
   `core`, `forms` y `executions` con sus plazos de conservación.
6. **Primer acceso.** Abre la portada en `/` y entra al panel con la cuenta
   administradora. Con la ruta predeterminada, el acceso es `/admin/login`.

Al terminar, el README generado recoge el nombre del sitio y el nombre público
del titular si el entorno permite escribirlo. Conserva esos nombres en el
checkout si el despliegue usa releases de solo lectura.

Si el servidor local se reinicia al guardar la configuración, espera a que
vuelva a mostrar la URL y recarga el paso del asistente.

### Recupera el acceso al asistente

Si pierdes la sesión o el asistente vuelve a pedir acceso tras el aprovisionamiento,
genera un código nuevo con PHP. En un proyecto Sail, ejecuta el segundo comando
desde la raíz de la web con Sail arrancado.

```bash
# PHP local
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
# Sail
./vendor/bin/sail php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Sustituye `CMS_INSTALL_TOKEN` en el `.env` de esa web. Abre `/install` e
introduce el código nuevo. Si el aprovisionamiento ya terminó, retoma en
`/install/admin` para configurar el sitio y crear la cuenta administradora.

## Seleccionar paquetes

`--theme`, `--admin-theme`, `--module`, `--block-package` y `--package` aceptan
listas separadas por comas. También puedes repetir cada opción. En las opciones
por tipo usa un nombre Composer completo o el sufijo de un paquete de
`dbravoan`, como `content-audit` en `--module`. En `--package` escribe siempre
`vendor/paquete`. Composer consulta Packagist por defecto. Core comprueba que
cada paquete indicado por tipo corresponda a la opción. Consulta también
`dbcms new --help`.

```bash
dbcms new mi-blog --theme=db-cyber,db-editorial --module=blog,gallery --sail
dbcms new mi-portal --admin-theme=termocms --block-package=before-after,scrollytelling
dbcms new mi-agenda --module='acme/dbcms-module-agenda:^0.1'
```

Se instalan todos los temas indicados y el primero de cada lista se preselecciona
en `/install`. Si omites la opción de un tipo de tema, Core toma el primero de
ese tipo incluido en `--package`. Puedes indicar una restricción de versión
tras `:`. Para combinar límites en un rango, sepáralos con espacios, como en
`--module='blog:>=0.0.1 <0.1,gallery'`.

Core lee los identificadores internos de los módulos y paquetes de bloques
descargados, incluidos los que llegan como dependencias. La selección se escribe
en `.env` y `.env.example`. El aprovisionamiento de `/install` instala y activa
los módulos y habilita los paquetes de bloques de la selección.

## Añadir módulos a una web existente

1. Desde la raíz de la web, añade el paquete y recompila los recursos. Este
   ejemplo instala el código de Blog.

   ```bash
   composer require 'dbravoan/dbcms-module-blog:~0.0.1'
   npm run build
   ```

   Con Sail, antepón `./vendor/bin/sail` a ambos comandos.
2. Abre **Sistema y ajustes → Módulos del sistema** en el panel y pulsa
   **Instalar y activar**. Core aplica las migraciones en ese paso. Si el módulo
   requiere otros módulos, instálalos y actívalos primero. Para un módulo ya
   instalado e inactivo, usa su control de estado para activarlo.
3. Completa los ajustes que indique el README del módulo y comprueba la función
   en el panel o en la web.

## Crear un módulo propio

### Define el paquete y el proveedor

Un módulo es un paquete Composer de tipo `dbcms-module` con su propio
repositorio. Antes de crearlo, busca un proveedor y una entrada equivalente
en Core o en un módulo existente. Usa sus contratos para permisos, mutaciones,
vistas y disponibilidad. [Código limpio y reutilización](https://github.com/dbravoan/dbcms-core/blob/main/docs/CONVENTIONS.md)

Para empezar con `Agenda`, crea el directorio del módulo desde la raíz de una
web de prueba. Sail comparte esa ubicación.

```bash
mkdir -p .local-modules/dbcms-module-agenda/src
git init -b main .local-modules/dbcms-module-agenda
```

Guarda este `composer.json` en `.local-modules/dbcms-module-agenda/`.

```json
{
    "name": "acme/dbcms-module-agenda",
    "description": "Módulo de agenda para dbCMS",
    "type": "dbcms-module",
    "license": "MIT",
    "require": {
        "php": "^8.4",
        "dbravoan/dbcms-core": "~0.0.1"
    },
    "autoload": {
        "psr-4": {"Acme\\Agenda\\": "src/"}
    },
    "extra": {
        "dbcms": {
            "identifier": "agenda",
            "name": "Agenda",
            "provider": "Acme\\Agenda\\AgendaServiceProvider"
        }
    }
}
```

Dentro del módulo, guarda el proveedor en `src/AgendaServiceProvider.php`.

```php
<?php

declare(strict_types=1);

namespace Acme\Agenda;

use DbCms\Core\Modules\BaseModuleServiceProvider;

final class AgendaServiceProvider extends BaseModuleServiceProvider
{
    protected string $identifier = 'agenda';

    protected string $name = 'Agenda';
}
```

Con estos archivos, Core puede descubrir e instalar `Agenda`. Implementa su
función en el paquete. Pon sus migraciones en `src/Database/Migrations` y sus
rutas de panel en `src/Routes/admin.php` cuando las necesites. Declara los
permisos del módulo en `extra.dbcms.permissions` o `module.json` y aplícalos a
las rutas correspondientes. Añade las dependencias Composer a `require`.

### Pruébalo en la web

Desde la raíz de la web de prueba, registra el módulo como repositorio `path`.
Composer lo enlaza al checkout para que puedas editarlo localmente.

```bash
composer config repositories.agenda '{"type":"path","url":".local-modules/dbcms-module-agenda","options":{"symlink":true,"versions":{"acme/dbcms-module-agenda":"dev-main"}}}'
composer require 'acme/dbcms-module-agenda:dev-main'
npm run build
```

Con Sail, antepón `./vendor/bin/sail` a los tres comandos. En **Módulos del
sistema**, instala y activa `Agenda`. Comprueba que quede activo y prueba la
función que añadiste. Usa el repositorio `path` en la web de prueba.

Escribe las pruebas en el paquete propietario y comprueba la entrada real en la
aplicación. Si el módulo tiene un arnés de paquete como Blog, define la composición
en `tests/package.php` y ejecuta sus comprobaciones. [Mantenimiento de Blog](https://github.com/dbravoan/dbcms-module-blog/blob/main/docs/MAINTENANCE.md)

### Usa el módulo en otras webs

Sube el código al repositorio del módulo y crea una etiqueta para la versión
que vas a publicar, por ejemplo `v0.1.0`. Registra el paquete en Packagist o en
el repositorio Composer que use la web. Cuando Composer encuentre esa versión,
usa `composer require 'acme/dbcms-module-agenda:^0.1'` en una web existente o
`dbcms new mi-web --package='acme/dbcms-module-agenda:^0.1'` para una nueva.

## Asociar la web a GitHub

Crea un repositorio vacío en GitHub. Desde la raíz de la web, revisa que Git
ignore `.env` y revisa los archivos antes de enviarlos. Sustituye `tu-cuenta`
por la cuenta que posee el repositorio.

```bash
git init -b main
git check-ignore .env
git add .
git diff --cached --name-only
git commit -m 'Crear la web con dbCMS'
git remote add origin git@github.com:tu-cuenta/mi-web.git
git push -u origin main
```

`dbcms new mi-web` genera el workflow `.github/workflows/deploy.yml` y sus
scripts para construir una release y desplegarla por SSH. Configura los
secretos del entorno `production` y activa `DEPLOY_ENABLED=true` según la guía
`docs/DEPLOYMENT.md` de la web generada. Si vas a gestionar el despliegue de otra
forma, usa `--deploy=none` al crear la web.
Antes del primer acceso por HTTPS a `/install`, prepara el código de instalación
en el servidor. [Variables de la primera instalación](docs/DEPLOYMENT.md#preparar-el-vps)

## Fallos y archivos existentes

El instalador usa un directorio temporal junto al destino y mueve la web al
nombre final cuando terminan Composer, la integración con Core y la compilación.
Si falla después de crear el temporal, el mensaje indica dónde encontrar
`.dbcms-install-failed.json` y la fase que falló. Puedes repetir el comando de
esa fase en el temporal para ver su salida.

Si Composer no resuelve un paquete, comprueba su nombre, restricción y
versiones disponibles en el repositorio configurado.
