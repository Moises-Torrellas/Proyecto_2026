-- ==========================================================
-- Archivo SQL para: Yonathan Mogollón
-- Módulos: Categoría, Cargos, Inventario de Artículos, Catálogo, Asignaciones, Torneos
-- ==========================================================

-- 1. Índices (4 - Propios)
CREATE INDEX idx_catalogo_busqueda ON catalogo(nombre, talla);
CREATE INDEX idx_catalogo_categoria ON catalogo(id_categoria);
CREATE INDEX idx_inventario_club ON articulos_inventario(codigo_club);
CREATE INDEX idx_inventario_estatus ON articulos_inventario(estatus, id_estado);

-- 2. Vistas (2 - 1 Propia y 1 asignada)
CREATE OR REPLACE VIEW vista_asignaciones_general AS
SELECT 
    a.id_asignacion, 
    DATE_FORMAT(a.fecha_asignacion, '%d/%m/%Y') AS fecha_vista,
    a.fecha_asignacion AS fecha_real,
    a.estatus AS estatus_asignacion,
    a.codigo_atleta,
    CONCAT(at.p_nombre, ' ', at.p_apellidos) AS atleta,
    CASE 
        WHEN ia.numero_doc IS NOT NULL AND ia.numero_doc <> '' THEN ia.numero_doc
        ELSE CONCAT('R-', r.cedula)
    END AS doc_identidad,
    c.nombre AS articulo,
    e.codigo_club,
    a.codigo_articulo
FROM asignaciones a
INNER JOIN atletas at ON a.codigo_atleta = at.codigo_atleta
LEFT JOIN identidad_atleta ia ON at.codigo_atleta = ia.codigo_atleta
LEFT JOIN atleta_representante ar ON at.codigo_atleta = ar.codigo_atleta
LEFT JOIN representantes r ON ar.codigo_representante = r.codigo_representante
INNER JOIN articulos_inventario e ON a.codigo_articulo = e.codigo_articulo
INNER JOIN catalogo c ON e.id_catalogo = c.id_catalogo;

DROP VIEW IF EXISTS `vista_cargos`;


-- 3. Triggers (2 - 1 Propio y 1 agregado)
DELIMITER 
CREATE TRIGGER trg_bloquear_articulos_danados
BEFORE UPDATE ON articulos_inventario
FOR EACH ROW
BEGIN
    IF NEW.id_estado = 2 THEN
        SET NEW.estatus = 3;
    END IF;
END
DELIMITER ;

DELIMITER //
CREATE TRIGGER trg_after_insert_asignacion
AFTER INSERT ON asignaciones
FOR EACH ROW
BEGIN
    UPDATE articulos_inventario 
    SET estatus = 2
    WHERE codigo_articulo = NEW.codigo_articulo;
END;
//
DELIMITER ;

-- 4. Procesos Almacenados con Transacciones (2 - Propios)
DELIMITER 
CREATE PROCEDURE RetirarArticuloSeguro(
    IN p_articulo INT,
    OUT p_resultado INT
)
BEGIN
    DECLARE v_estatus TINYINT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
    END;

    START TRANSACTION;
    SELECT estatus INTO v_estatus FROM articulos_inventario WHERE codigo_articulo = p_articulo FOR UPDATE;

    IF v_estatus IS NULL THEN
        SET p_resultado = -1;
        ROLLBACK;
    ELSEIF v_estatus != 1 THEN
        SET p_resultado = -2;
        ROLLBACK;
    ELSE
        UPDATE articulos_inventario SET estatus = 3 WHERE codigo_articulo = p_articulo;
        COMMIT;
        SET p_resultado = 1;
    END IF;
END

CREATE PROCEDURE EliminarCatalogoSeguro(
    IN p_id_catalogo INT,
    OUT p_resultado INT
)
BEGIN
    DECLARE v_existe INT;
    DECLARE v_en_uso INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0; 
    END;

    START TRANSACTION;
    SELECT COUNT(*) INTO v_existe FROM catalogo WHERE id_catalogo = p_id_catalogo FOR UPDATE;
    SELECT COUNT(*) INTO v_en_uso FROM articulos_inventario WHERE id_catalogo = p_id_catalogo;

    IF v_existe = 0 THEN
        SET p_resultado = -1;
        ROLLBACK;
    ELSEIF v_en_uso > 0 THEN
        SET p_resultado = -2;
        ROLLBACK;
    ELSE
        DELETE FROM catalogo WHERE id_catalogo = p_id_catalogo;
        COMMIT;
        SET p_resultado = 1;
    END IF;
END
DELIMITER ;

-- 5. Funciones Almacenadas (2 - Propias)
DELIMITER 
CREATE FUNCTION StockDisponibleCatalogo(p_id_catalogo INT) 
RETURNS INT READS SQL DATA
BEGIN
    DECLARE v_total INT;
    SELECT COUNT(*) INTO v_total FROM articulos_inventario 
    WHERE id_catalogo = p_id_catalogo AND estatus = 1 AND id_estado = 1;
    RETURN v_total;
END

CREATE FUNCTION EsAptoParaUso(p_id_estado INT) 
RETURNS TINYINT READS SQL DATA
BEGIN
    DECLARE v_nivel TINYINT;
    SELECT nivel_estado INTO v_nivel FROM estado_fisico WHERE id_estado = p_id_estado;
    RETURN IF(v_nivel = 1, 1, 0);
END
DELIMITER ;

-- 6. Subconsultas (2 - Propias adaptadas como consultas)
-- Subconsulta 1: Artículos Libres
SELECT e.codigo_articulo, e.codigo_club, 
       IFNULL((SELECT nombre FROM catalogo c WHERE c.id_catalogo = e.id_catalogo), 'Artículo sin registrar') as articulo 
FROM articulos_inventario e 
WHERE e.estatus = 1;

-- Subconsulta 2: Catalogo con nombres de categoria anidado
SELECT c.*, 
       (SELECT cat.nombre FROM categoria_catalogo cat WHERE cat.id_categoria = c.id_categoria) as categoria_nombre,
       StockDisponibleCatalogo(c.id_catalogo) AS stock_actual
FROM catalogo c;
