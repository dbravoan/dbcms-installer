# Trabajar en esta web dbCMS

Lee [README.md](README.md) para identificar el sitio, su composición inicial y los comandos. Consulta los contratos de Core que afecten al cambio. Con las dependencias instaladas, están en `vendor/dbravoan/dbcms-core/docs/`.

## Antes de programar

1. Sigue la entrada de usuario, ruta, capacidad MCP o vista hasta el paquete propietario. Lee su documentación y prueba equivalente.
2. Busca el servicio, esquema, bloque, plantilla o componente que ya resuelve el caso. Consulta `docs/CONVENTIONS.md` de Core, los paquetes de `composer.lock` y el catálogo del sitio cuando edites contenido.
3. Reutiliza ese punto de entrada y sus validaciones. Si falta una operación común, amplía el dueño existente. Usa nombres de dominio, controladores delgados y datos preparados antes de Blade. Comprueba el resultado desde la entrada habitual.

## Dónde programar

- Usa `App` para código propio, `routes/web.php` para rutas de la aplicación y `resources/` para vistas y recursos propios. Registra las páginas Blade mediante el contrato de Core y usa el constructor para el contenido por secciones. [Arquitectura](https://github.com/dbravoan/dbcms-core/blob/main/docs/CORE_AND_EXTENSIONS.md)
- Desarrolla las funciones compartidas en el repositorio propietario de Core, el módulo, el tema o el paquete de bloques. Actualiza los paquetes con Composer. [Contratos de extensión](https://github.com/dbravoan/dbcms-core/blob/main/docs/EXTENSIONS.md)
- Al añadir una pantalla, prepara los datos antes de Blade y reutiliza el kit de administración. Al guardar contenido, usa los servicios de dominio y respeta permisos, idiomas, mercados y revisión editorial. Para editar por MCP, aplica la guía de composición a las secciones y la guía de temas a los campos de una plantilla Blade. [Convenciones](https://github.com/dbravoan/dbcms-core/blob/main/docs/CONVENTIONS.md) · [Guía MCP](https://github.com/dbravoan/dbcms-installer/blob/main/docs/HOW_TO_MCP.md)

## Cómo verificar

Ejecuta PHP, Composer, Artisan y las pruebas en el entorno que indica el README. Usa una aplicación y una base desechables para probar instalaciones y migraciones. Mantén fuera de Git los secretos y los datos del entorno. Comprueba el recorrido del usuario, ejecuta las pruebas pertinentes e indica qué has verificado. [Pruebas de Core](https://github.com/dbravoan/dbcms-core/blob/main/docs/TESTING.md)

## Arneses del proyecto

Estas guías se pueden abrir directamente, con cualquier editor o asistente.

- [Módulos propios](.agents/skills/dbcms-modulos/SKILL.md) para crear un paquete, registrar su proveedor y comprobar permisos y ciclo de vida.
- [Paquetes de bloques](.agents/skills/dbcms-bloques/SKILL.md) para extender el registro, esquemas, editor y render de bloques.
- [Temas y vistas](.agents/skills/dbcms-temas/SKILL.md) para cambiar presentación, registrar una página Blade o extender recursos.
- [Composición y plantillas](.agents/skills/dbcms-composicion/SKILL.md) para maquetar páginas y artículos desde el constructor o MCP.

| Cliente | Cómo acceder desde la raíz de la web |
| --- | --- |
| Codex | Selecciona un skill desde el selector o invócalo por nombre, por ejemplo `$dbcms-modulos`. También puedes pedir el subagente `dbcms_extensiones` o `dbcms_composicion`. |
| OpenCode | Usa `/dbcms` seguido de la tarea o menciona `@dbcms-extensiones` o `@dbcms-composicion`. |
| Gemini CLI | Usa `/dbcms` seguido de la tarea. Si tu cliente tiene subagentes habilitados, pide `dbcms-extensiones` o `dbcms-composicion`. |
| Claude Code | `CLAUDE.md` carga esta guía. Pide que abra el skill enlazado para trabajar con su procedimiento. |
