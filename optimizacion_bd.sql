-- =================================================================
-- SCRIPT DE OPTIMIZACIÓN DE BASE DE DATOS: FUNCIONES, TRIGGERS Y PROCEDIMIENTOS
-- =================================================================
-- Base de datos: cannibalsbd
-- =================================================================

USE `cannibalsbd`;

-- -----------------------------------------------------------------
-- 1. FUNCIÓN: CalcularMontoAbonado
-- -----------------------------------------------------------------
-- Calcula la edad exacta de un atleta basándose en su fecha de nacimiento.
-- Es mucho más preciso que restar los años en PHP.
DROP FUNCTION IF EXISTS `ObtenerMontoAbonado`;

DELIMITER //
CREATE FUNCTION ObtenerMontoAbonado(p_codigo_cargo INT) RETURNS DECIMAL(10,2)
READS SQL DATA
BEGIN
    DECLARE total DECIMAL(10,2);
    
    SELECT COALESCE(SUM(dp.monto_abonado), 0.00) INTO total
    FROM detalles_pagos dp
    INNER JOIN pagos p ON dp.codigo_pago = p.codigo_pago
    WHERE dp.codigo_cargo = p_codigo_cargo 
    AND p.estatus = 1;
    
    RETURN total;
END //
DELIMITER ;


-- -----------------------------------------------------------------
-- 2. DISPARADOR (TRIGGER): DespuesDeInsertarDevolucion
-- -----------------------------------------------------------------
-- Actualiza automáticamente el estado del equipamiento cuando un
-- atleta devuelve un equipo, sin necesidad de hacerlo desde PHP.
DROP TRIGGER IF EXISTS `DespuesDeInsertarDevolucion`;

DELIMITER //
CREATE TRIGGER DespuesDeInsertarDevolucion
AFTER INSERT ON devoluciones
FOR EACH ROW
BEGIN
    DECLARE v_id_equipamiento INT;
    
    -- 1. Buscamos qué equipamiento estaba en esa asignación
    SELECT id_equipamiento INTO v_id_equipamiento 
    FROM asignaciones 
    WHERE id_asignacion = NEW.id_asignacion LIMIT 1;
    
    -- 2. Actualizamos el equipamiento: 
    -- estatus = 1 (Disponible) y actualizamos su condición física (id_estados)
    UPDATE equipamientos 
    SET estatus = 1, id_estados = NEW.id_estado 
    WHERE id_equipamiento = v_id_equipamiento;
    
    -- 3. Marcamos la asignación como devuelta/inactiva (estatus = 0)
    UPDATE asignaciones
    SET estatus = 0
    WHERE id_asignacion = NEW.id_asignacion;
END //
DELIMITER ;


-- -----------------------------------------------------------------
-- 3. PROCEDIMIENTO ALMACENADO CON TRANSACCIÓN: RegistrarPagoCompleto
-- -----------------------------------------------------------------
-- Inserta el pago, el detalle del pago y actualiza la deuda del atleta
-- en una sola operación segura (Todo o Nada).
DROP PROCEDURE IF EXISTS `RegistrarPagoCompleto`;

DELIMITER //
CREATE PROCEDURE RegistrarPagoCompleto(
    IN p_id_metodo INT,
    IN p_id_moneda INT,
    IN p_monto_pago DECIMAL(10,2),
    IN p_monto_vuelto DECIMAL(10,2),
    IN p_fecha DATE,
    IN p_referencia VARCHAR(40),
    IN p_id_cobrar INT,
    IN p_monto_abonado DECIMAL(10,2),
    IN p_tasa_cambio DECIMAL(10,4),
    OUT p_resultado INT
)
BEGIN
    DECLARE v_id_pago INT;
    
    -- Manejador de errores: Si algo falla, se deshace todo (ROLLBACK)
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0; -- Indicador de error
    END;

    -- INICIAMOS LA TRANSACCIÓN
    START TRANSACTION;

    -- Paso 1: Insertar el registro principal del Pago
    INSERT INTO pagos (id_metodo, id_moneda, monto_pago, monto_vuelto, fecha, referencia, estatus)
    VALUES (p_id_metodo, p_id_moneda, p_monto_pago, p_monto_vuelto, p_fecha, p_referencia, 1);
    
    -- Obtenemos el ID generado automáticamente para el nuevo pago
    SET v_id_pago = LAST_INSERT_ID();

    -- Paso 2: Insertar el detalle del pago (asociando la cuenta por cobrar)
    INSERT INTO detalles_pagos (id_pago, id_cobrar, monto_abonado, tasa_cambio)
    VALUES (v_id_pago, p_id_cobrar, p_monto_abonado, p_tasa_cambio);

    -- Paso 3: Actualizar la cuenta por cobrar del atleta
    UPDATE cuentas_cobrar 
    SET 
        monto_pendiente = monto_pendiente - p_monto_abonado,
        -- Si ya no debe nada (o el monto pendiente es 0), cambiamos estatus a 1 (Pagado)
        estatus = IF(monto_pendiente - p_monto_abonado <= 0, 1, 0) 
    WHERE id_cobrar = p_id_cobrar;

    -- Si todos los pasos anteriores fueron exitosos, GUARDAMOS LOS CAMBIOS
    COMMIT;
    
    -- Indicador de éxito
    SET p_resultado = 1;
END //
DELIMITER ;
