# Sistema de Gestión Administrativo - Cannibals Lara (Windows)

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

# Sistema de Gestión Administrativo - Cannibals Lara (Instalacion en Linux)

## Guía Integral de Instalación del Entorno LAMP y Despliegue del Sistema

### 1. Actualización del Sistema
Antes de comenzar, actualiza la lista de paquetes del sistema operativo para asegurar la instalación de las versiones más recientes:
```bash
sudo apt update
```
### 2. Instalación y Configuración de Apache (Servidor Web)
Instala el servidor web Apache:
```bash
sudo apt install apache2 -y
```

Habilita el módulo de reescritura (mod_rewrite), el cual es esencial para que funcionen las rutas y el archivo .htaccess en sistemas PHP:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 3. Instalación de PHP y Extensiones Requeridas
Instala PHP junto con el módulo de Apache, el conector de MySQL y las extensiones necesarias, incluyendo php-xml para evitar errores de dom:

```bash
sudo apt install php libapache2-mod-php php-mysql php-gd php-intl php-zip php-xml -y
```

### 4. Transferencia de Archivos a la Máquina Virtual

Antes de configurar la base de datos y el servidor, necesitas pasar el código del sistema y los archivos `.sql` a Ubuntu. Puedes usar cualquiera de estos tres métodos:

#### Método A: Usar un Repositorio de Git (Recomendado)
Ideal si tu código está en GitHub, GitLab o Bitbucket.
1. Instala Git en Ubuntu: `sudo apt install git -y`
2. Navega a tu carpeta de Descargas o Documentos.
3. Clona tu repositorio: `git clone https://github.com/tu_usuario/tu_repositorio.git`

#### Método B: Arrastrar y Soltar (VirtualBox)
Para pasar archivos rápidamente usando el ratón.
1. En el menú superior de VirtualBox, ve a **Dispositivos > Arrastrar y soltar > Bidireccional**.
2. Opcional: Activa también **Dispositivos > Portapapeles compartido > Bidireccional**.
3. Arrastra los archivos desde tu computadora física y suéltalos en el escritorio de Ubuntu.

#### Método C: Carpeta Compartida (VirtualBox)
Crea una carpeta puente entre tu PC física y Ubuntu.
1. En tu PC física, crea una carpeta y guarda allí tus archivos.
2. En VirtualBox, ve a **Dispositivos > Carpetas compartidas > Preferencias...**
3. Añade la carpeta marcando las opciones **Automontar** y **Hacer permanente**.
4. En Ubuntu, da permisos a tu usuario ejecutando: `sudo usermod -aG vboxsf $USER`
5. Reinicia Ubuntu. Tus archivos estarán disponibles en la ruta `/media/sf_nombre_de_carpeta/`.

### 5. Instalación de Composer
Instala el gestor de dependencias de PHP:

```bash
sudo apt install composer -y
```


### 6. Preparación del Proyecto (Instalación de dependencias)
Navega a la carpeta de tu proyecto (por ejemplo, en Descargas) antes de moverlo al servidor para evitar conflictos de permisos con root:

```bash
cd ~/Descargas/Proyecto_2026
composer install
```

### 7. Instalación y Configuración de MySQL (Base de Datos)
Instala el servidor de bases de datos:

```bash
sudo apt install mariadb-server -y
```

Accede a la consola de Mariadb con:

```bash
sudo mariadb
```
*(Si alguna vez te llegara a pedir contraseña para entrar al usuario root de la base de datos (por seguridad extra), simplemente usa:)*
```bash
sudo mariadb -u root -p
```

Ejecuta los siguientes comandos para crear las bases de datos y el usuario:

Nombre_de_Usuario: Ej(moises).
Contraseña_Deseada: Ej(123456).

```bash
CREATE DATABASE bds;
CREATE DATABASE cannibalsbd;
CREATE USER 'Nombre_de_Usuario'@'localhost' IDENTIFIED BY 'Contraseña_Deseada';
GRANT ALL PRIVILEGES ON bds.* TO 'Nombre_de_Usuario'@'localhost';
GRANT ALL PRIVILEGES ON cannibalsbd.* TO 'Nombre_de_Usuario'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 8. Transferencia de Archivos y Despliegue
Copia el proyecto ya preparado al directorio raíz de Apache:

```bash
sudo cp -r ~/Descargas/Proyecto_2026 /var/www/html/
```

### 9. Configuración de Credenciales
Para editar las credenciales de conexión directamente en la carpeta del servidor, abre el archivo con el editor nano:

```bash
sudo nano /var/www/html/Proyecto_2026/config/config.php
```
*(Para guardar presiona Ctrl + O, luego Enter, y para salir Ctrl + X)*

### 10. Permisos Finales y Activación
Asigna la propiedad de los archivos al usuario del servidor web (www-data) y otorga permisos:

```bash
sudo chown -R www-data:www-data /var/www/html/Proyecto_2026
sudo chmod -R 755 /var/www/html/Proyecto_2026
```

Configura Apache para permitir el uso de .htaccess:

```bash
sudo nano /etc/apache2/apache2.conf
```
*(Busca el bloque <Directory /var/www/> y cambia AllowOverride None por AllowOverride All)*

Reinicia Apache para aplicar todos los cambios:

```bash
sudo systemctl restart apache2
```

---

### Guía de Comandos para Terminal (Gestión de Archivos)

* **¿Cuándo usar sudo su?**: Evítalo. Usa `sudo` anteponiéndolo a cada comando para mantener la seguridad y permisos correctos.
* **Copiar**: `sudo cp -r [origen] [destino]`
* **Mover/Cortar**: `sudo mv [origen] [destino]`
* **Borrar (Permanente)**: `sudo rm -rf /var/www/html/Proyecto_2026`
* **Cambiar de carpeta**: `cd /ruta/a/carpeta`
* **Listar archivos**: `ls -l`