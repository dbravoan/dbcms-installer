---
name: dbcms-modulos
description: Crea o amplía módulos propios de dbCMS con Composer, proveedores, permisos, migraciones y capacidades del CMS.
---

# Módulos propios en dbCMS

1. Lee `AGENTS.md` y consulta `docs/CONVENTIONS.md` y `docs/EXTENSIONS.md` en el Core instalado. Busca un módulo equivalente y sigue su ruta, esquema y servicio. Reutiliza su punto de extensión cuando cubra el caso.
2. Declara namespace, proveedor e identificador en Composer. Usa `BaseModuleServiceProvider` y registra entidades, permisos, migraciones y rutas en su dueño. Mantén los controladores delgados.
3. Reutiliza los esquemas y servicios de mutación de Core para revisión, idioma y mercado. Para el panel usa `x-admin.*`, el selector de medios y las clases `admin-*`. Para contenido público parte del lector propietario y el contexto compartido.
4. Instala el paquete con Composer en una web desechable, recompila los recursos cuando corresponda e instala y activa el módulo desde el CMS. Comprueba al menos una operación del módulo en su entrada normal y el comportamiento al desactivar su disponibilidad.

## Ejemplo de un área privada de clientes para juegos

El ejemplo del instalador gestiona las cuentas de jugador y protege el área y los juegos con un guard propio. Si los clientes usan credenciales propias, consulta `registerModuleAuth()` en `BaseModuleServiceProvider` y comprueba el acceso con una cuenta de jugador y con una sesión anónima. Si el módulo necesita restringir juegos por cuenta, declara y prueba esa autorización. [Guía del área privada](https://github.com/dbravoan/dbcms-installer/blob/main/docs/HOW_TO_BUILD_CLIENT_AREA.md)
