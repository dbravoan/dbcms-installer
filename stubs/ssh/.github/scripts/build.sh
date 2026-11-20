#!/usr/bin/env bash
set -euo pipefail
: "${GITHUB_SHA:?}"
[[ $GITHUB_SHA =~ ^[0-9a-f]{40}$ ]] || { printf 'SHA inválido.\n' >&2; exit 1; }
artifact=${1:?Falta la ruta del artefacto}
[[ -f composer.lock && -f package-lock.json && -f .env.example && ! -L .env.example ]]
# Solo se leen los cuatro valores públicos del instalador; nunca se ejecuta .env.
for key in CMS_THEME CMS_BRAND CMS_MODULES CMS_BLOCK_PACKAGES; do
  value=$(sed -n "s/^$key=//p" .env.example)
  [[ $value =~ ^[a-zA-Z0-9_,\-]*$ ]] || { printf 'Composición inválida en %s.\n' "$key" >&2; exit 1; }
  export "$key=$value"
done
composer install --no-dev --no-scripts --prefer-dist --no-interaction --no-progress --optimize-autoloader
npm ci --include=dev --no-audit --no-fund
npm run build
mkdir -p bootstrap/cache
rm -f bootstrap/cache/*.php bootstrap/cache/*.json
printf '%s\n' "$GITHUB_SHA" > DEPLOYMENT_ID
tar -czf "$artifact" --exclude=.git --exclude=.github --exclude=.build --exclude=node_modules \
  --exclude=tests --exclude=docker --exclude=storage --exclude=public/storage --exclude=public/uploads \
  --exclude='.env' --exclude='.env.*' --exclude=auth.json --exclude=.npmrc --exclude=.netrc \
  --exclude=compose.yaml --exclude=phpunit.xml .
