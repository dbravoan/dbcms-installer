Necesitas Docker con Compose. En una web nueva con el MySQL de Sail, usa
`DB_HOST=mysql` y `DB_PORT=3306`. Escribe `DB_DATABASE`, `DB_USERNAME` y
`DB_PASSWORD` en `.env` para que Compose pueda crear una base y un usuario
de desarrollo. Si cambias `APP_PORT`, usa ese puerto en `APP_URL` y en el
navegador.

Si `./vendor/bin/sail` todavía no existe, instala `vendor` desde la raíz del
checkout con Composer en Docker. Esta primera pasada prepara los archivos de
Sail a partir de `composer.lock`. Las comprobaciones de PHP y los scripts de
Composer se ejecutan después dentro del contenedor de la web.

```bash
docker run --rm --user "$(id -u):$(id -g)" -v "$PWD:/app" -w /app \
  composer:2 install --no-interaction --prefer-dist --no-scripts --ignore-platform-reqs
```

```bash
./vendor/bin/sail up -d
./vendor/bin/sail composer install --no-interaction --prefer-dist
./vendor/bin/sail npm ci
./vendor/bin/sail npm run build
```

Para una web nueva con `APP_KEY` vacía, ejecuta `./vendor/bin/sail artisan key:generate`.
Si falta `CMS_INSTALL_TOKEN`, genera un valor con el siguiente comando y
guárdalo en `.env` con ese nombre antes de abrir `/install`.

```bash
./vendor/bin/sail php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

En una web restaurada, si falta `public/storage`, crea el enlace con
`./vendor/bin/sail artisan storage:link`.
