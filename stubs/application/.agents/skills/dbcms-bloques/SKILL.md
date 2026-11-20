---
name: dbcms-bloques
description: Implementa o extiende un tipo de bloque dbCMS, su esquema, render y recursos del editor y de la web.
---

# Programar bloques dbCMS

1. Lee `AGENTS.md` y el contrato de bloques en `vendor/dbravoan/dbcms-core/docs/EXTENSIONS.md`. Consulta el catálogo del destino. Reutiliza o compón tipos registrados que ya expresan la función.
2. Si necesitas un tipo propio, elige su dueño. Regístralo en el proveedor del módulo o en `extra.dbcms.blocks` de un paquete visual. Extiende `AbstractBlockType` y declara esquema, valores de ejemplo y rutas traducibles en el bloque.
3. Usa las zonas `blocks` y los repetidores con ID del esquema. Reutiliza la validación, apariencia, editor, biblioteca y render de Core. Prepara los datos antes de Blade y usa los lectores publicados del idioma y mercado activos.
4. Si el bloque necesita un editor o runtime propio, integra sus recursos en los puntos de Vite del paquete. Mantén accesibilidad, lectura sin movimiento y limpieza de recursos en la interacción.
5. Verifica registro y disponibilidad, guardado desde el constructor o MCP, vista previa y render público. Usa pruebas del propietario para validar el esquema y sus efectos.
