# Cómo empezar con dbCMS en David Bravo

Crea una web local con el tema público `db-cyber`, el panel `dbcms` y Blog. Usa una base MySQL vacía y datos de prueba si reproduces el ejemplo.

## 1. Prepara y arranca la aplicación

Necesitas PHP 8.4, Composer 2, Node.js 22.12 o posterior y MySQL. Usa una base vacía y un usuario con permisos para crear y modificar sus tablas. Los pasos generales de instalación y las opciones de Sail están en el [README del instalador](../README.md).

Instala el CLI y crea la web desde un directorio donde aún no exista `davidbravo`.

```bash
composer global require 'dbravoan/dbcms-installer:~0.2.0'
export PATH="$(composer global config bin-dir --absolute):$PATH"
dbcms new davidbravo --no-interaction --theme=db-cyber --module=blog
cd davidbravo
php artisan serve
```

El CLI integra los paquetes con Laravel y compila los recursos. Abre `/install` en la URL que indique el servidor. Para crear la web con Docker y Sail, sigue el [arranque con contenedores](../README.md#sail-y-contenedores).

![Salida de dbcms new con el enlace al asistente](how-to-start/00-salida-instalador.png)

Revisa el `README.md` generado para consultar la composición inicial y
`AGENTS.md` para acceder a los arneses de desarrollo. Installer también
prepara `.github/workflows/deploy.yml` y su [guía de despliegue SSH](DEPLOYMENT.md).
El workflow se activa cuando configuras los secretos y `DEPLOY_ENABLED=true`.

## 2. Abre el asistente y comprueba los requisitos

Ve a `/install`. La cabecera muestra **>_dbcms**. Copia el valor de
`CMS_INSTALL_TOKEN` del `.env` del sitio y pulsa **Abrir el asistente**.
El código se puede usar una vez y autoriza esa sesión del navegador.

![Acceso al asistente con el campo del código vacío](how-to-start/01-acceso.png)

Comprueba que las extensiones de PHP y los permisos de escritura aparezcan en verde. Pulsa **Continuar a la Base de Datos**.

![Extensiones de PHP y permisos de escritura verificados](how-to-start/02-requisitos.png)

## 3. Conecta MySQL

Introduce servidor, puerto, base de datos, usuario y contraseña. Con Sail, usa `mysql` y `3306` y consulta las credenciales en `.env`. Usa el host que pueda resolver el proceso de PHP. Si PHP y MySQL se ejecutan directamente en el mismo equipo, suele ser `127.0.0.1`.

Con una base ya creada, desmarca **Crear la base de datos automáticamente**. Pulsa **Probar Conexión**. Cuando la pantalla confirme la conexión, pulsa **Guardar y Continuar**.

![Conexión MySQL confirmada, con la contraseña enmascarada](how-to-start/03-conexion-mysql.png)

## 4. Aprovisiona la instalación limpia

Selecciona **Instalación Limpia (Solo Estructura)** y pulsa **Iniciar Aprovisionamiento**. dbCMS aplica las migraciones, activa los módulos seleccionados y prepara el almacenamiento y OAuth2.

![Modo de instalación limpia seleccionado](how-to-start/04-instalacion-limpia.png)

Cuando aparezca **Aprovisionamiento finalizado con éxito**, pulsa
**Siguiente: Administrador**. Si el asistente vuelve a pedir acceso,
[genera otro código y retoma la instalación](../README.md#recupera-el-acceso-al-asistente).

![Aprovisionamiento completado y enlace para continuar](how-to-start/05-aprovisionamiento-completo.png)

## 5. Configura el sitio, la cuenta y la privacidad

### Sitio

Sustituye el nombre inicial **dbCMS** por **David Bravo** e introduce la URL
completa del sitio, incluido el puerto si lo usas. Deja **admin** como
ruta del panel para que el inicio de sesión quede en `/admin/login`. Pulsa
**Continuar**.

![Identidad del sitio, tema seleccionado y ruta del panel](how-to-start/06-sitio.png)

### Administrador

Introduce el nombre y el correo de la persona administradora. Escribe y confirma su contraseña, guárdala en un gestor de contraseñas y pulsa **Continuar**.

![Formulario de la cuenta administradora antes de escribir la contraseña](how-to-start/07-administrador.png)

### Titular

Rellena los datos del responsable, su dirección y los correos de contacto y
privacidad. Marca las opciones sobre actividad económica, registro o delegado de
protección de datos cuando correspondan. El asistente indica qué nombres incluye
en el README. Pulsa **Continuar**.

![Datos ficticios del titular en el asistente](how-to-start/08-titular.png)

### Privacidad

Indica el proveedor y país de alojamiento. Describe los tratamientos que usa tu sitio, incluidos `core`, `forms` y `executions`. Para cada uno, indica la finalidad, la base jurídica, los datos, la conservación, los destinatarios y las transferencias que correspondan. Las capturas muestran datos de ejemplo.

![Alojamiento y primer tratamiento en la pantalla de privacidad](how-to-start/09-privacidad.png)

Marca la casilla de revisión y pulsa **Finalizar instalación**.

![Confirmación de revisión y botón para finalizar la instalación](how-to-start/10-revision-final.png)

## 6. Comprueba el primer acceso al panel

La confirmación conserva la cabecera **>_dbcms** y muestra **David Bravo** en
el mensaje de éxito. Desde ahí puedes abrir el panel y la portada. El README
generado completa **Nombre del sitio** y **Titular**.
Después de completar la instalación, `/install` abre el inicio de sesión.

![Confirmación de instalación y enlaces al panel y a la portada](how-to-start/11-instalacion-completada.png)

Pulsa **Acceder al Panel de Control** e inicia sesión con la cuenta que acabas de crear. Si aparece el tour de bienvenida, puedes seguirlo o pulsar **Saltar Tour** para ver el panel.

![Pantalla de inicio de sesión del panel](how-to-start/13-inicio-de-sesion.png)

![Tour inicial del panel de administración](how-to-start/14-tour-inicial.png)

![Panel con la sesión de la persona administradora activa](how-to-start/15-panel.png)

Abre **Sistema y ajustes → Módulos del sistema** y comprueba que **Blog Engine** figure como **Activo**. En **Blog → Artículos del blog** puedes crear un artículo.

![Módulo Blog activo](how-to-start/16-blog-activo.png)

![Gestor de artículos de Blog listo para empezar](how-to-start/17-gestor-articulos.png)

## 7. Publica una primera portada

Desde **Páginas**, crea una página con el título **David Bravo** y el slug `home`. Activa la publicación para que la página sirva como portada en `/`.

![Creación de la página publicada con slug home](how-to-start/18-crear-portada.png)

Pulsa **Añadir primera sección**. En la columna, elige **Añadir un bloque a
esta columna**, selecciona **Título** y pulsa **En blanco**. Escribe
**Bienvenido a David Bravo**, guarda la página y abre `/` en otra pestaña.

![Primer bloque de título en el constructor](how-to-start/20-primer-bloque.png)

![Página pública de David Bravo con el primer bloque](how-to-start/19-portada-configurada.png)

Vuelve a **Páginas** para editar la portada y añadir sus primeras secciones y bloques.

El servidor MCP que incluye Core se configura en **Sistema y ajustes →
Servidor MCP**. El recorrido continúa en la [guía de MCP con capturas](HOW_TO_MCP.md).
