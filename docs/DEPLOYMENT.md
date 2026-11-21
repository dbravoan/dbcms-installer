# Despliegue SSH de la aplicación

`dbcms new` incluye `.github/workflows/deploy.yml` por defecto. El workflow
construye la aplicación a partir de sus locks en un push a `main` o en una
ejecución manual desde esa rama. Transfiere el artefacto por SSH y lo activa
como release en el VPS. El despliegue requiere configurar los secretos y
poner `DEPLOY_ENABLED=true`.

## Preparar el VPS

Usa Linux con Bash, PHP 8.4 y extensiones de Composer, HTTPS, MySQL, `tar`, `curl`
y `flock`. El usuario de aplicación debe poseer una ruta canónica sin enlaces,
por ejemplo `/var/www/mi-web`. Configura el document root en `current/public`:

```text
/var/www/mi-web/
  .env
  storage/
  uploads/
  releases/
  current -> releases/<release>
```

El preview editorial usa un iframe aislado. Permite CORS únicamente en los
recursos públicos compilados; por ejemplo, en el servidor Nginx de esta web,
antes de otras reglas de archivos estáticos:

```nginx
location ~ ^/build/assets/.*\.(css|js|mjs|woff2?|ttf|otf|svg|png|jpe?g|gif|webp|avif|ico|mp4|webm|mp3|ogg|wav|json|wasm)$ {
    try_files $uri =404;
    add_header Access-Control-Allow-Origin "*";
}
```

No añadas esa cabecera a HTML, API ni respuestas con credenciales. En desarrollo,
`php artisan serve` y Sail usan el `server.php` preparado por `cms:setup`.

Prepara `.env` fuera de Git, partiendo de `.env.example`, que conserva los temas,
módulos y bloques elegidos. Configura `APP_ENV=production`, `APP_DEBUG=false`,
`APP_MAINTENANCE_DRIVER=file` y la URL HTTPS. El archivo debe ser privado (`0600`)
y legible por PHP y los workers. En una instalación nueva, genera la `APP_KEY`
una sola vez. Genera también un código con
`php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'` y guárdalo como
`CMS_INSTALL_TOKEN` en el `.env` del servidor antes de abrir `/install`.

Conserva `APP_KEY`, `CMS_INSTALLATION_ID`, claves OAuth, `.env`, `storage` y medios
entre releases. Antes de completar `/install`, usa `CACHE_STORE=file`,
`SESSION_DRIVER=file` y `QUEUE_CONNECTION=sync`. Los workers y el scheduler se
configuran en el servidor según los módulos realmente usados.

## Configurar GitHub

En el entorno `production` de **este repositorio**, configura:

| Secret | Valor |
| --- | --- |
| `VPS_HOST`, `VPS_USER` | Servidor y usuario de aplicación |
| `VPS_DIR` | Ruta absoluta anterior |
| `VPS_SSH_KEY` | Clave privada autorizada en ese VPS |
| `VPS_SSH_FINGERPRINT` | Huella SHA256 del host, verificada por un canal fiable |
| `VPS_PORT` | Puerto SSH; por defecto 22 |

La clave del VPS permite que Actions se conecte al servidor. Es independiente de
tu SSH local para hacer push y no es una deploy key de repositorio GitHub.
El instalador no crea ni registra ninguna clave.

Configura como variables **del repositorio** `DEPLOY_ENABLED=true` y
`DEPLOY_URL=https://tu-dominio`.
Opcionalmente, `VPS_PHP_BINARY` selecciona PHP y `VPS_FPM_SERVICE` el servicio FPM
que se recarga con `sudo -n systemctl reload`; autoriza solo ese comando concreto.
Para dependencias privadas puedes aportar el secret `COMPOSER_AUTH`.

## Desplegar

Versiona `composer.lock`, `package-lock.json`, `.env.example` y los archivos
generados. Revisa y prueba la web antes de integrar en `main`.

El workflow ejecuta `composer install --no-dev` y `npm ci`, compila los recursos y
transfiere un artefacto sin `.env`, datos, cachés ni herramientas de desarrollo.
No necesita Composer o Node en producción. Verifica la huella SSH antes de enviar.

En la primera instalación no ejecuta migraciones ni genera cachés: el asistente
debe poder cambiar conexión y rutas antes de fijarlas. Activa el código y
comprueba `/install`. Completa después el asistente web con MySQL, administrador y datos
legales. En instalaciones existentes entra en mantenimiento, ejecuta
`php artisan migrate --force --no-interaction` y `cms:access:sync`, prepara cachés
y cambia `current` atómicamente. Core carga las migraciones del núcleo y de los
módulos configurados o ya instalados; no activa módulos nuevos por su cuenta.
Comprueba que los workers en curso pueden continuar durante el despliegue o
páusalos desde el supervisor antes de iniciarlo.

La comprobación pública exige `/up` correcto y la identidad del PHP que sirve
`/_deployment/health`. Conserva cuatro releases, incluidos el activo y el anterior.
`.env`, `storage` y `uploads` quedan fuera de esa limpieza.

## Recuperar un fallo

Mantén una copia verificable de base de datos y archivos antes de actualizar.
Un fallo después de entrar en mantenimiento conserva el marcador
`deployment-recovery-required`, los logs y el mantenimiento; bloquea nuevos
despliegues hasta revisarlo. El marcador identifica release y código anterior.

Corrige el problema o restaura un conjunto coherente de código, base de datos y
archivos. Volver a un symlink anterior no revierte migraciones. No hay rollback
automático ni se ejecuta `migrate:rollback`. Después de verificar salud e identidad,
retira conscientemente el marcador, ejecuta `php artisan up` desde `current` y
reinicia los procesos necesarios antes de repetir el workflow.
