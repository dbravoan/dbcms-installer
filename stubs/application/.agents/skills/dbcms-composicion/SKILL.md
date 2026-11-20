---
name: dbcms-composicion
description: Maqueta páginas, artículos y plantillas reutilizables de dbCMS con secciones, columnas y bloques editables en el constructor o mediante MCP.
---

# Composición editable en dbCMS

1. Lee `AGENTS.md`. Define el propósito de cada sección y busca plantillas y bloques reutilizables en el idioma y mercado de destino. Usa el constructor o MCP según la tarea. Si hace falta programar un tipo de bloque, aplica el skill [paquetes de bloques](../dbcms-bloques/SKILL.md).
2. Por MCP, empieza por las herramientas y los esquemas que este servidor anuncia. Usa cms_block_types para descubrir los bloques y disposiciones y consulta los esquemas elegidos. Cuando el catálogo lo ofrezca, usa cms_block_templates para copiar una plantilla publicada y cms_block_references para consultar referencias en el contexto.
3. Usa `sections_json` para crear borradores con la estructura idioma → secciones → columnas → bloques. Reserva su propia sección para un bloque de ancho completo y genera identificadores nuevos al repetir una plantilla en la misma composición.
4. Usa Rich Text para contenido editorial con formato. Descubre el tipo y su esquema con el catálogo. Conserva el formato de un bloque al corregirlo.
5. Lee la composición con `cms_content_sections_get`. Usa su versión y los IDs con `cms_content_sections_patch`. Para colecciones con ID, consulta `view: "authoring"` y edita mediante los destinos que declara el esquema. Revisa el resultado en el constructor y sigue el flujo de aprobación del sitio.

Consulta los [recorridos con Rich Text](https://github.com/dbravoan/dbcms-core/blob/main/docs/HOW_TO_RICH_TEXT_LANDINGS.md) para construir una landing desde el panel o MCP.
