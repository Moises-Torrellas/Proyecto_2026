# Sistema de Gestión Administrativo - Cannibals Lara

Este proyecto es una plataforma integral para la gestión de atletas, representantes, estadísticas, pagos e inventario del club de hockey.

---

## 🛠️ Requisitos del Entorno (Configuración de XAMPP)

Para que el sistema funcione correctamente (especialmente la generación de reportes PDF con Dompdf, procesamiento de imágenes con GD y manejo de archivos comprimidos), es obligatorio activar algunas extensiones en tu servidor local. Sigue estos pasos detallados:

1. Abre el XAMPP Control Panel.
2. En la línea del servicio Apache, haz clic en el botón Config y selecciona PHP (php.ini). Esto abrirá el archivo de configuración global de PHP en el Bloc de Notas.
3. Presiona la combinación de teclas Ctrl + B (o Ctrl + F si tu sistema operativo está en inglés) para abrir el buscador de texto.
4. Busca las siguientes extensiones (notarás que tienen un punto y coma ";" al principio, lo que significa que están comentadas y desactivadas):
   ;extension=gd
   ;extension=intl
   ;extension=zip
5. Actívalas eliminando el punto y coma (";") del principio de cada línea. Deben quedar exactamente así:
   extension=gd
   extension=intl
   extension=zip
6. Guarda el archivo presionando Ctrl + G (o Archivo > Guardar o Ctrl + S si tu sistema operativo está en inglés) y cierra el bloc de notas.
7. Reinicia el servicio Apache: En el Panel de XAMPP, haz clic en el botón Stop al lado de Apache, espera un par de segundos a que se apague por completo, y luego haz clic en Start.

---

## 🚀 Instalación y Despliegue

Sigue estos pasos en orden estricto para montar el proyecto en tu computadora local:

### 1. Clonar el repositorio

Abre una terminal o consola de comandos Git dentro de tu carpeta raíz de proyectos (htdocs en tu directorio de XAMPP) y ejecuta:

#### git clone https://github.com/Moises-Torrellas/Proyecto_2026.git

### 2. Instalar dependencias de Composer

Para descargar de forma automatizada todas las librerías necesarias del proyecto que están estructuradas mediante el autoloader PSR-4 (como Dompdf, PHPMailer, Ratchet para WebSockets, etc.), ejecuta en tu consola:

#### composer install

Nota: Si el comando te arroja un error de dependencias, verifica que hayas guardado correctamente el archivo php.ini y reiniciado el servicio Apache como se explicó en los requisitos del entorno.

### 3. Cargar las Bases de Datos (MySQL / MariaDB)

⚠️ INFORMACIÓN CRÍTICA PARA LA IMPORTACIÓN: NO es necesario crear ninguna base de datos manualmente antes de importar. Los archivos SQL ya incluyen internamente la sentencia estructural "CREATE DATABASE IF NOT EXISTS". El gestor creará los contenedores de datos y todas sus tablas de manera completamente automática.

El sistema administrativo utiliza dos bases de datos que deben cargarse en el siguiente orden desde phpMyAdmin (http://localhost/phpmyadmin/):

#### Paso A: Importar la Base de Datos Principal

1. Entra a phpMyAdmin y haz clic directamente en la pestaña "Importar" (Import) ubicada en la barra de navegación superior (sin seleccionar ninguna base de datos de la columna izquierda).
2. Haz clic en el botón "Seleccionar archivo" (Choose File) y busca el archivo .sql de la base de datos principal del sistema.
3. Desplázate hacia el final de la página y haz clic en el botón "Importar" (o Continuar).
4. Espera a que el sistema muestre el mensaje verde de éxito.

#### Paso B: Importar la Base de Datos de Seguridad

1. Vuelve a hacer clic en el logo de phpMyAdmin arriba a la izquierda para asegurarte de estar en la raíz del servidor.
2. Haz clic de nuevo en la pestaña "Importar" de la barra superior.
3. Haz clic en "Seleccionar archivo" y esta vez elige el archivo .sql correspondiente a la bitácora del sistema.
4. Desplázate hacia abajo y haz clic en el botón "Importar" (o Continuar).

Una vez completados ambos pasos, verás las dos bases de datos creadas y listas con todas sus respectivas tablas en la columna de la izquierda de tu phpMyAdmin.