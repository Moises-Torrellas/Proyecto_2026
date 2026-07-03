CREATE TABLE `roles` (
  `id_rol` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(35) NOT NULL,
  `descripcion` varchar(50) DEFAULT 'Sin Descripcin',
  `nivel_rol` tinyint NOT NULL DEFAULT 3,
  `estatus` tinyint NOT NULL DEFAULT 1
);

CREATE TABLE `modulo` (
  `id_modulo` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `nombre_modulo` varchar(50) NOT NULL,
  `nombre_code` varchar(50) NOT NULL,
  `descripcion` varchar(100) NOT NULL,
  `estatus` tinyint NOT NULL DEFAULT 1
);

CREATE TABLE `usuarios` (
  `idUsuario` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `cedulaUsuario` varchar(10) UNIQUE NOT NULL,
  `nombreUsuario` varchar(35) NOT NULL,
  `apellidoUsuario` varchar(35) NOT NULL,
  `foto` varchar(255) NOT NULL DEFAULT 'default.png',
  `telefonoUsuario` varchar(15) NOT NULL,
  `pass_hash` varchar(255) NOT NULL,
  `correo` varchar(60) UNIQUE NOT NULL,
  `id_rol` int NOT NULL,
  `intentos_fallidos` int NOT NULL DEFAULT 0,
  `estatus` tinyint NOT NULL DEFAULT 1,
  `bloqueo` tinyint NOT NULL DEFAULT 1
);

CREATE TABLE `permisos_rol` (
  `id_permiso_rol` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_permiso` int NOT NULL,
  `id_rol` int NOT NULL
);

CREATE TABLE `excepciones` (
  `id_excepcion` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_permiso` int NOT NULL,
  `id_usuario` int NOT NULL,
  `tipo` tinyint NOT NULL DEFAULT 2
);

CREATE TABLE `permisos` (
  `id_permiso` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_modulo` int NOT NULL,
  `clave` varchar(50) NOT NULL,
  `descripcion` varchar(50) NOT NULL,
  `estatus` tinyint NOT NULL DEFAULT 1
);

CREATE TABLE `bitacora` (
  `id_bitacora` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_modulo` int NOT NULL,
  `acciones` varchar(255) NOT NULL,
  `datos_previos` varchar(255) NOT NULL,
  `datos_nuevos` varchar(255) NOT NULL,
  `entorno` varchar(50) NOT NULL,
  `fecha_hora` timestamp NOT NULL,
  `idUsuario` int NOT NULL
);

CREATE TABLE `notificaciones` (
  `id_notificacion` int PRIMARY KEY NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` text NOT NULL,
  `tipo` tinyint NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT (now()),
  `estatus` tinyint NOT NULL DEFAULT 1
);

CREATE TABLE `respaldos` (
  `id_respaldo` int PRIMARY KEY AUTO_INCREMENT,
  `id_usuario` int,
  `nombre_archivo` varchar(100),
  `peso` varchar(20),
  `fecha_creacion` datetime
);

ALTER TABLE `permisos_rol` ADD FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`) ON DELETE CASCADE;

ALTER TABLE `bitacora` ADD FOREIGN KEY (`idUsuario`) REFERENCES `usuarios` (`idUsuario`);

ALTER TABLE `bitacora` ADD FOREIGN KEY (`id_modulo`) REFERENCES `modulo` (`id_modulo`);

ALTER TABLE `notificaciones` ADD FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`idUsuario`);

ALTER TABLE `usuarios` ADD FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`);

ALTER TABLE `permisos` ADD FOREIGN KEY (`id_modulo`) REFERENCES `modulo` (`id_modulo`);

ALTER TABLE `permisos_rol` ADD FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`);

ALTER TABLE `excepciones` ADD FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`);

ALTER TABLE `excepciones` ADD FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`idUsuario`);

ALTER TABLE `respaldos` ADD FOREIGN KEY (`id_respaldo`) REFERENCES `usuarios` (`idUsuario`);
