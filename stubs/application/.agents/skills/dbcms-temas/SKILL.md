---
name: dbcms-temas
description: Personaliza un sitio dbCMS con un tema, vistas Blade, recursos Vite, páginas Blade registradas y componentes del backoffice.
---

# Temas y vistas en dbCMS

1. Lee `AGENTS.md` y el README de la web. Identifica el tema efectivo y consulta las secciones Vistas, temas y assets y Páginas Blade del contrato de extensiones de Core.
2. Busca el layout, parcial o bloque equivalente en el tema y Core. Usa `FrontViewResolver`, las regiones y los componentes compartidos, incluidos navegación y consentimiento. Coloca los cambios propios en la aplicación y los compartidos en el paquete propietario. Compila con Vite.
3. Para una página Blade gestionada por el CMS, registra su clave y utiliza el modo Blade de Page mediante el contrato de Core. Para una página editable por secciones, usa el constructor y la guía de composición.
4. Declara los campos editables en la definición de la vista. Usa text para una línea y rich_text para contenido con formato. Edita los valores por idioma desde el panel o en translations.<idioma>.template_data por MCP. Consulta los tipos que anuncia cms_entity_types.
5. Prepara los datos antes de Blade. En el panel reutiliza `x-admin.*`, el selector de medios y los tokens de la marca. Comprueba la vista, los controles y el teclado en escritorio y móvil con el tema, idioma y mercado de destino.

Sigue los [recorridos de páginas Blade con Rich Text](https://github.com/dbravoan/dbcms-core/blob/main/docs/HOW_TO_RICH_TEXT_LANDINGS.md) para comprobar la edición en el navegador.
