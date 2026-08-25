-- ==========================================================
-- Archivo SQL para: Yessica Meléndez
-- Módulos: Métodos de Pago, Monedas, Devoluciones, Premios
-- ==========================================================

-- 1. Índices (2)
CREATE INDEX indice_codigo_moneda ON monedas (codigo_moneda);
CREATE INDEX indice_fecha_devolucion ON devoluciones (fecha_devolucion);

-- 2. Vistas (2)

DROP VIEW IF EXISTS ista_resumen_devoluciones;
CREATE VIEW vista_resumen_devoluciones AS
SELECT 
    d.id_devolucion, 
    DATE_FORMAT(d.fecha_devolucion, '%Y-%m-%d') as fecha_vista,
    d.fecha_devolucion, d.id_asignacion, d.id_estado, d.observacion, 
    ee.nombre as estado_fisico, ee.nivel_estado, at.codigo_atleta, at.p_nombre as atleta_nombre,
    at.p_apellidos as atleta_apellido, cat.nombre as articulo_nombre,
    (SELECT COUNT(*) FROM devoluciones d2 
     INNER JOIN asignaciones a2 ON d2.id_asignacion = a2.id_asignacion 
     WHERE a2.codigo_atleta = at.codigo_atleta) as total_devoluciones_atleta
FROM devoluciones d
INNER JOIN asignaciones asig ON d.id_asignacion = asig.id_asignacion
INNER JOIN atletas at ON asig.codigo_atleta = at.codigo_atleta
INNER JOIN estado_fisico ee ON d.id_estado = ee.id_estado
INNER JOIN articulos_inventario eq ON asig.codigo_articulo = eq.codigo_articulo
INNER JOIN catalogo cat ON eq.id_catalogo = cat.id_catalogo;

-- 3. Triggers (2)
DELIMITER //
CREATE TRIGGER trg_despues_insertar_devolucion
AFTER INSERT ON devoluciones
FOR EACH ROW
BEGIN
    UPDATE articulos_inventario 
    SET estatus = 1, id_estado = NEW.id_estado
    WHERE codigo_articulo = (SELECT codigo_articulo FROM asignaciones WHERE id_asignacion = NEW.id_asignacion);
END;
//

CREATE TRIGGER trg_antes_actualizar_moneda
BEFORE UPDATE ON monedas
FOR EACH ROW
BEGIN
    IF OLD.abreviatura != NEW.abreviatura THEN
        SIGNAL SQLSTATE \'45000\' SET MESSAGE_TEXT = \'No se permite modificar la abreviatura de la moneda\';
    END IF;
END;
//
DELIMITER ;

-- 4. Procesos Almacenados con Transacciones (2)
DELIMITER //
CREATE PROCEDURE ProcesarDevolucionSegura (
    IN p_id_asignacion INT, 
    IN p_id_estado INT,
    IN p_observacion VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;
    
    UPDATE asignaciones SET estatus = 0 WHERE id_asignacion = p_id_asignacion;
    
    INSERT INTO devoluciones (id_asignacion, id_estado, fecha_devolucion, observacion) 
    VALUES (p_id_asignacion, p_id_estado, CURDATE(), p_observacion);
    
    COMMIT;
END;
//

CREATE PROCEDURE RegistrarPremioSeguro (
    IN p_id_atleta INT,
    IN p_descripcion VARCHAR(255),
    IN p_monto_premio DECIMAL(10,2)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;
    
    INSERT INTO premios (id_atleta, descripcion, monto, fecha_entrega) 
    VALUES (p_id_atleta, p_descripcion, p_monto_premio, CURDATE());
    
    COMMIT;
END;
//
DELIMITER ;



CREATE FUNCTION TotalDevolucionesMes (p_mes INT, p_anio INT) 
RETURNS INT DETERMINISTIC
BEGIN
    DECLARE v_total INT;
    SELECT COUNT(*) INTO v_total FROM devoluciones WHERE MONTH(fecha_devolucion) = p_mes AND YEAR(fecha_devolucion) = p_anio;
    RETURN COALESCE(v_total, 0);
END;
//
DELIMITER ;

-- 6. Subconsultas (2)
-- Subconsulta 1: Atletas que han devuelto artículos
SELECT p_nombre, p_apellidos 
FROM atletas 
WHERE codigo_atleta IN (SELECT a.codigo_atleta FROM asignaciones a INNER JOIN devoluciones d ON a.id_asignacion = d.id_asignacion);

-- Subconsulta 2: Monedas más utilizadas en pagos
SELECT nombre 
FROM monedas 
WHERE codigo_moneda IN (SELECT codigo_moneda FROM pagos GROUP BY codigo_moneda HAVING COUNT(*) > 2);
