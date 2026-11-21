En una web nueva, rellena `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` y
`DB_PASSWORD` con la conexión a tu MySQL vacío. Desde la raíz del checkout,
instala las dependencias y compila los recursos.

```bash
composer install --no-interaction
npm ci
npm run build
```

Para una web nueva con `APP_KEY` vacía, ejecuta `php artisan key:generate`.
Si falta `CMS_INSTALL_TOKEN`, genera un valor con el siguiente comando y
guárdalo en `.env` con ese nombre.

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

En una web restaurada, si falta el enlace `public/storage`, créalo con
`php artisan storage:link`. Arranca con `php artisan serve` y abre `/install`
si es el primer aprovisionamiento.
