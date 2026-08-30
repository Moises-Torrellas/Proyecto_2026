-- ==========================================================
-- SCRIPT DE OBJETOS DE BASE DE DATOS PARA SEGURIDAD (BDS2)
-- ==========================================================
-- Incluye: Índices, Vistas, Triggers, Procesos Almacenados,
-- Transacciones (dentro de los P.A.) y Funciones Almacenadas.
-- ==========================================================

USE `bds2`;

-- ==========================================================
-- 1. ÍNDICES (2 Objetos)
-- ==========================================================

-- Índice 1: Optimiza las búsquedas en la bitácora por rango de fechas, muy útil para auditorías.
CREATE INDEX indice_bitacora_fecha ON bitacora(fecha_hora);

-- Índice 2: Optimiza la búsqueda de usuarios combinando su rol y estatus.
CREATE INDEX indice_usuarios_rol_estatus ON usuarios(id_rol, estatus);


-- ==========================================================
-- 2. VISTAS (3 Objetos basados en el requerimiento)
-- ==========================================================

-- Borrar vistas previas (si existían)
DROP VIEW IF EXISTS vista_usuarios_roles;
DROP VIEW IF EXISTS vista_bitacora_detallada;
DROP VIEW IF EXISTS vista_consulta_usuarios;
DROP VIEW IF EXISTS vista_consulta_permisos;
DROP VIEW IF EXISTS vista_consulta_bitacora;

-- Vista 1: vista_consulta_usuarios (basada en el Modelo de Usuarios)
CREATE VIEW vista_consulta_usuarios AS
SELECT 
    u.idUsuario,
    u.cedulaUsuario,
    u.nombreUsuario,
    u.apellidoUsuario,
    u.foto,
    u.telefonoUsuario,
    u.correo,
    u.id_rol,
    u.bloqueo,
    r.nombre_rol,
    u.ultimo_ingreso 
FROM `usuarios` u 
INNER JOIN roles r ON r.id_rol = u.id_rol 
WHERE u.estatus != 0;

-- Vista 2: vista_consulta_permisos (basada en el Modelo de Permisos)
CREATE VIEW vista_consulta_permisos AS
SELECT 
    p.id_permiso, 
    p.nombre AS nombre_permiso, 
    p.clave, 
    p.descripcion, 
    p.estatus AS estatus_permiso, 
    m.id_modulo, 
    m.nombre_modulo, 
    m.estatus AS estatus_modulo,
    m.icono 
FROM permisos p 
INNER JOIN modulos m ON p.id_modulo = m.id_modulo;

-- Vista 3: vista_consulta_bitacora (basada en el Modelo de Bitácora)
CREATE VIEW vista_consulta_bitacora AS
SELECT 
    b.id_bitacora,
    u.nombreUsuario,
    u.apellidoUsuario,
    u.cedulaUsuario,
    m.nombre_modulo,
    m.icono,
    b.acciones,
    b.datos_previos,
    b.datos_nuevos,
    b.entorno,
    DATE(b.fecha_hora) AS fecha,
    TIME(b.fecha_hora) AS hora 
FROM bitacora b
INNER JOIN usuarios u ON u.idUsuario = b.idUsuario
INNER JOIN modulos m ON m.id_modulo = b.id_modulo;


-- ==========================================================
-- 3. TRIGGERS (2 Objetos)
-- ==========================================================

DELIMITER //

-- Eliminamos triggers previos según solicitud
DROP TRIGGER IF EXISTS trg_before_update_usuario //
DROP TRIGGER IF EXISTS trg_after_insert_usuario //
DROP TRIGGER IF EXISTS disparador_antes_actualizar_usuario //
DROP TRIGGER IF EXISTS disparador_despues_insertar_usuario //
DROP TRIGGER IF EXISTS disparador_antes_borrar_rol //

-- Trigger 1: disparador_despues_insertar_usuario
-- Registra automáticamente en la bitácora la creación de un nuevo usuario en el sistema.
CREATE TRIGGER disparador_despues_insertar_usuario
AFTER INSERT ON usuarios
FOR EACH ROW
BEGIN
    DECLARE v_nombre_rol VARCHAR(35);
    
    -- Obtenemos el nombre del rol para que sea legible
    SELECT nombre_rol INTO v_nombre_rol FROM roles WHERE id_rol = NEW.id_rol LIMIT 1;
    
    INSERT INTO bitacora (id_modulo, acciones, datos_previos, datos_nuevos, entorno, idUsuario)
    VALUES (
        1, -- Asumiendo que 1 es el módulo de Seguridad
        CONCAT('Registro al usuario: ', NEW.cedulaUsuario, ' - ', NEW.nombreUsuario, ' ', NEW.apellidoUsuario),
        'No Aplica',
        CONCAT('Cédula: ', NEW.cedulaUsuario, ', Nombre: ', NEW.nombreUsuario, ' ', NEW.apellidoUsuario, ', Correo: ', NEW.correo, ', Teléfono: ', NEW.telefonoUsuario, ', Rol: ', IFNULL(v_nombre_rol, 'Desconocido')),
        'Base de Datos',
        IFNULL(@usuario_actual, NEW.idUsuario) -- Se toma el usuario de la sesión de BD o el creado si es el primero
    );
END //



-- ==========================================================
-- 4 y 5. PROCESOS ALMACENADOS Y TRANSACCIONES (2 Objetos)
-- Según solicitud: Reemplazar los anteriores por procedimientos 
-- de incluir usuario e incluir bitacora con transacciones explícitas.
-- ==========================================================

DELIMITER //

-- Borramos procedimientos anteriores
DROP PROCEDURE IF EXISTS sp_resetear_intentos //
DROP PROCEDURE IF EXISTS sp_bloquear_inactivos //
DROP PROCEDURE IF EXISTS sp_eliminar_usuario_seguro //
DROP PROCEDURE IF EXISTS sp_transferir_usuarios_rol //
DROP PROCEDURE IF EXISTS pa_incluir_usuario //
DROP PROCEDURE IF EXISTS pa_incluir_bitacora //

-- Procedimiento / Transacción 1: pa_incluir_usuario
-- Inserta un usuario con soporte transaccional para garantizar integridad.
CREATE PROCEDURE pa_incluir_usuario(
    IN p_cedula VARCHAR(10),
    IN p_nombre VARCHAR(35),
    IN p_apellido VARCHAR(35),
    IN p_foto VARCHAR(255),
    IN p_telefono VARCHAR(15),
    IN p_contra VARCHAR(255),
    IN p_correo VARCHAR(60),
    IN p_rol INT,
    OUT p_resultado INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        -- Si hay cualquier error SQL, se revierte la transacción
        ROLLBACK;
        SET p_resultado = 0;
    END;

    START TRANSACTION;
    
    -- Operación principal: Insertar el usuario
    INSERT INTO `usuarios`
        (`cedulaUsuario`, `nombreUsuario`, `apellidoUsuario`, `foto`, `telefonoUsuario`, `pass_hash`, `correo`, `id_rol`, `estatus`, `bloqueo`, `intentos_fallidos`) 
    VALUES 
        (p_cedula, p_nombre, p_apellido, p_foto, p_telefono, p_contra, p_correo, p_rol, 1, 1, 0);
    
    -- Si la inserción ocurre sin problemas, hacemos commit
    COMMIT;
    SET p_resultado = 1;
END //

-- Procedimiento / Transacción 2: pa_incluir_bitacora
-- Registra un evento en la bitácora garantizando su inserción atómica mediante una transacción.
DROP PROCEDURE IF EXISTS `RegistrarVueltoSeguro` //
CREATE PROCEDURE `RegistrarVueltoSeguro` (
    IN `p_codigo_metodo` INT, 
    IN `p_codigo_pago` INT, 
    IN `p_codigo_moneda` INT, 
    IN `p_monto_vuelto` DECIMAL(10,2), 
    IN `p_fecha_vuelto` DATE, 
    IN `p_referencia` VARCHAR(255),
    IN `p_monto_base` DECIMAL(10,2)
)   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;
    INSERT INTO vueltos (codigo_metodo, codigo_pago, codigo_moneda, monto_vuelto, fecha_vuelto, referencia, monto_base) 
    VALUES (p_codigo_metodo, p_codigo_pago, p_codigo_moneda, p_monto_vuelto, p_fecha_vuelto, p_referencia, p_monto_base);
    COMMIT;
END //

CREATE PROCEDURE pa_incluir_bitacora(
    IN p_modulo INT,
    IN p_acciones VARCHAR(255),
    IN p_previos VARCHAR(255),
    IN p_nuevos VARCHAR(255),
    IN p_entorno VARCHAR(50),
    IN p_usuario INT,
    OUT p_resultado INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        -- Revierte en caso de falla
        ROLLBACK;
        SET p_resultado = 0;
    END;

    START TRANSACTION;
    
    -- Insertar el log de auditoría
    INSERT INTO bitacora 
        (id_modulo, acciones, datos_previos, datos_nuevos, entorno, idUsuario)
    VALUES 
        (p_modulo, p_acciones, p_previos, p_nuevos, p_entorno, p_usuario);
        
    -- Si fue exitoso, persistimos en la base de datos
    COMMIT;
    SET p_resultado = 1;
END //

DELIMITER ;


-- ==========================================================
-- 6. FUNCIONES ALMACENADAS (2 Objetos)
-- Nombres cambiados al español y ajustada según requerimiento.
-- ==========================================================

DELIMITER //

-- Borramos funciones previas si existían
DROP FUNCTION IF EXISTS fn_obtener_nombre_rol //
DROP FUNCTION IF EXISTS fn_estado_cuenta_usuario //
DROP FUNCTION IF EXISTS funcion_obtener_nombre_rol //
DROP FUNCTION IF EXISTS funcion_estado_cuenta_usuario //

-- Función 1: funcion_obtener_nombre_rol
-- Devuelve el nombre del rol dado su ID.
CREATE FUNCTION funcion_obtener_nombre_rol(
    p_id_rol INT
) RETURNS VARCHAR(35)
DETERMINISTIC
BEGIN
    DECLARE v_nombre VARCHAR(35);
    
    SELECT nombre_rol INTO v_nombre 
    FROM roles 
    WHERE id_rol = p_id_rol 
    LIMIT 1;
    
    RETURN IFNULL(v_nombre, 'Rol Desconocido');
END //

-- Función 2: funcion_estado_cuenta_usuario
-- Evalúa exclusivamente si la cuenta está bloqueada o desbloqueada.
CREATE FUNCTION funcion_estado_cuenta_usuario(
    p_id_usuario INT
) RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    DECLARE v_bloqueo TINYINT;
    DECLARE v_resultado VARCHAR(20);
    
    SELECT bloqueo INTO v_bloqueo 
    FROM usuarios 
    WHERE idUsuario = p_id_usuario 
    LIMIT 1;
    
    -- Lógica solicitada: 0 es Bloqueado, 1 es Desbloqueado. Sin inactivo.
    IF v_bloqueo = 0 THEN
        SET v_resultado = 'Bloqueado';
    ELSE
        SET v_resultado = 'Desbloqueado';
    END IF;
    
    RETURN v_resultado;
END //

DELIMITER ;
