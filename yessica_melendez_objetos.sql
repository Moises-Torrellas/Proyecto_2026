-- ==========================================================
-- Archivo SQL para: Yessica Meléndez
-- Módulos: Métodos de Pago, Monedas, Devoluciones, Premios
-- ==========================================================

-- 1. Índices (2)
CREATE INDEX indice_codigo_moneda ON monedas (codigo_moneda);
CREATE INDEX indice_fecha_devolucion ON devoluciones (fecha_devolucion);

-- 2. Vistas (2)

DROP VIEW IF EXISTS vista_resumen_devoluciones;
CREATE VIEW vista_resumen_devoluciones AS
SELECT 
    d.id_devolucion, 
    DATE_FORMAT(d.fecha_devolucion, '%Y-%m-%d') as fecha_vista,
    d.fecha_devolucion, d.id_asignacion, d.id_estado, d.observacion, 
    ee.nombre as estado_fisico, ee.nivel_estado, at.codigo_atleta, at.p_nombre as atleta_nombre,
    at.p_apellidos as atleta_apellido, 
    CASE WHEN ia.numero_doc IS NOT NULL AND ia.numero_doc <> '' THEN ia.numero_doc ELSE CONCAT('R-', r.cedula) END as doc_identidad, 
    cat.nombre as articulo_nombre,
    (SELECT COUNT(*) FROM devoluciones d2 
     INNER JOIN asignaciones a2 ON d2.id_asignacion = a2.id_asignacion 
     WHERE a2.codigo_atleta = at.codigo_atleta) as total_devoluciones_atleta
FROM devoluciones d
INNER JOIN asignaciones asig ON d.id_asignacion = asig.id_asignacion
INNER JOIN atletas at ON asig.codigo_atleta = at.codigo_atleta
LEFT JOIN identidad_atleta ia ON at.codigo_atleta = ia.codigo_atleta
LEFT JOIN atleta_representante ar ON at.codigo_atleta = ar.codigo_atleta
LEFT JOIN representantes r ON ar.codigo_representante = r.codigo_representante
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


-- 4. Procesos Almacenados con Transacciones (2)
DROP PROCEDURE IF EXISTS ProcesarDevolucionSegura;

DELIMITER //

CREATE PROCEDURE ProcesarDevolucionSegura(
    IN p_id_asignacion INT, 
    IN p_id_estado INT,
    IN p_observacion VARCHAR(255)
)
BEGIN
    DECLARE v_estatus INT;
    
    -- Manejador de errores para hacer rollback automático si algo falla
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL; -- Reenvía el error a PHP para que lo capture
    END;

    START TRANSACTION;
    
    -- 1. Consultamos el estatus actual bloqueando la fila (FOR UPDATE)
    SELECT estatus INTO v_estatus 
    FROM asignaciones 
    WHERE id_asignacion = p_id_asignacion 
    FOR UPDATE;
    
    -- 2. Validamos que la asignación siga estando activa (Estatus 1 = En Uso)
    IF v_estatus = 1 THEN
        
        -- Cambiamos el estatus (asumiendo que 2 significa "Devuelto" o inactivo)
        UPDATE asignaciones SET estatus = 2 WHERE id_asignacion = p_id_asignacion;
        
        -- Registramos la devolución
        INSERT INTO devoluciones (id_asignacion, id_estado, fecha_devolucion, observacion) 
        VALUES (p_id_asignacion, p_id_estado, CURDATE(), p_observacion);
        
        COMMIT;
        
    ELSE
        -- Si el estatus no es 1, abortamos lanzando una alerta que atrapará PHP
        ROLLBACK;
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Error de Concurrencia: Esta asignación ya fue devuelta o procesada por otro usuario.';
    END IF;

END //

DELIMITER ;


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



DELIMITER //
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
