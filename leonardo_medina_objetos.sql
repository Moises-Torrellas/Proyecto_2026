-- ==========================================================
-- Archivo SQL para: Leonardo Medina
-- Módulos: Conceptos de Cargos, Equipos, Participaciones, Palmarés, Estadísticas
-- ==========================================================

-- 1. Índices (2)
CREATE INDEX indice_torneo_participaciones ON participaciones (codigo_torneo);
CREATE INDEX indice_equipo_participaciones ON participaciones (codigo_equipo);

-- 2. Vistas (4)
DROP VIEW IF EXISTS vista_atletas_asignacion;
CREATE VIEW vista_atletas_asignacion AS
SELECT a.codigo_atleta AS id_atleta,
       MAX(ia.numero_doc) AS doc_identidad,
       MAX(a.p_nombre) AS nombres,
       MAX(a.p_apellidos) AS apellidos,
       MAX(c.nombre) AS nombre_categoria,
       MAX(p.nombre) AS nombre_posicion
FROM atletas a
LEFT JOIN identidad_atleta ia ON a.codigo_atleta = ia.codigo_atleta
INNER JOIN inscripciones i ON a.codigo_atleta = i.codigo_atleta
INNER JOIN categorias c ON c.codigo_categoria = i.codigo_categoria
INNER JOIN posiciones p ON p.codigo_posicion = i.codigo_posicion
WHERE i.estatus = 1
GROUP BY a.codigo_atleta;

DROP VIEW IF EXISTS ista_atletas_equipo;
CREATE VIEW vista_atletas_equipo AS
SELECT a.codigo_atleta AS id_atleta,
       de.codigo_equipo AS id_equipo,
       MAX(ia.numero_doc) AS doc_identidad,
       MAX(a.p_nombre) AS nombres,
       MAX(a.p_apellidos) AS apellidos,
       MAX(c.nombre) AS nombre_categoria,
       MAX(p.nombre) AS nombre_posicion
FROM detalles_equipos de
INNER JOIN atletas a ON a.codigo_atleta = de.codigo_atleta
LEFT JOIN identidad_atleta ia ON a.codigo_atleta = de.codigo_atleta
INNER JOIN inscripciones i ON a.codigo_atleta = i.codigo_atleta
INNER JOIN categorias c ON c.codigo_categoria = i.codigo_categoria
INNER JOIN posiciones p ON p.codigo_posicion = i.codigo_posicion
WHERE i.estatus = 1
GROUP BY a.codigo_atleta, de.codigo_equipo;

DROP VIEW IF EXISTS ista_palmares_individual;
CREATE VIEW vista_palmares_individual AS
SELECT 
    pi.codigo_individual AS id_individual,
    pi.codigo_premio AS id_premio,
    dp.codigo_atleta AS id_atleta,
    a.p_nombre AS atleta_nombres,
    a.p_apellidos AS atleta_apellidos,
    a.foto AS atleta_foto,
    p.nombre AS nombre_premio,
    p.tipo AS tipo_premio,
    part.codigo_torneo AS id_torneo,
    t.nombre AS nombre_torneo,
    t.fecha_inicio AS fecha_torneo
FROM palmares_individual pi
INNER JOIN premios p ON pi.codigo_premio = p.codigo_premio
INNER JOIN detalles_participacion dp ON pi.codigo_dtll_prtc = dp.codigo_dtll_prtc
INNER JOIN atletas a ON dp.codigo_atleta = a.codigo_atleta
INNER JOIN participaciones part ON dp.codigo_participacion = part.codigo_participacion
INNER JOIN torneos t ON part.codigo_torneo = t.codigo_torneo;

DROP VIEW IF EXISTS ista_palmares_grupal;
CREATE VIEW vista_palmares_grupal AS
SELECT 
    pg.codigo_grupal,
    pg.codigo_premio AS id_premio,
    part.codigo_equipo AS id_equipo,
    e.nombre AS nombre_equipo,
    p.nombre AS nombre_premio,
    p.tipo AS tipo_premio,
    part.codigo_torneo AS id_torneo,
    t.nombre AS nombre_torneo,
    t.fecha_inicio AS fecha_torneo
FROM palmares_grupal pg
INNER JOIN premios p ON pg.codigo_premio = p.codigo_premio
INNER JOIN participaciones part ON pg.codigo_participacion = part.codigo_participacion
INNER JOIN equipos e ON part.codigo_equipo = e.codigo_equipo
INNER JOIN torneos t ON part.codigo_torneo = t.codigo_torneo;


-- 3. Triggers (2)
DELIMITER //
CREATE TRIGGER trg_antes_insertar_palmares_individual
BEFORE INSERT ON palmares_individual
FOR EACH ROW
BEGIN
    DECLARE v_estatus TINYINT;
    
    SELECT t.estatus INTO v_estatus
    FROM torneos t
    INNER JOIN participaciones p ON t.codigo_torneo = p.codigo_torneo
    INNER JOIN detalles_participacion dp ON p.codigo_participacion = dp.codigo_participacion
    WHERE dp.codigo_dtll_prtc = NEW.codigo_dtll_prtc;
    
    IF v_estatus != 3 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: No se puede registrar palmarés en un torneo que no ha finalizado.';
    END IF;
END;
//

CREATE TRIGGER trg_antes_insertar_palmares_grupal
BEFORE INSERT ON palmares_grupal
FOR EACH ROW
BEGIN
    DECLARE v_estatus TINYINT;
    
    SELECT t.estatus INTO v_estatus
    FROM torneos t
    INNER JOIN participaciones p ON t.codigo_torneo = p.codigo_torneo
    WHERE p.codigo_participacion = NEW.codigo_participacion;
    
    IF v_estatus != 3 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: No se puede registrar palmarés en un torneo que no ha finalizado.';
    END IF;
END;
//


-- 4. Procesos Almacenados con Transacciones (2)
DELIMITER //
CREATE PROCEDURE RegistrarParticipacionSegura (
    IN p_id_equipo INT, 
    IN p_id_torneo INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;
    INSERT INTO participaciones (codigo_equipo, codigo_torneo) 
    VALUES (p_id_equipo, p_id_torneo);
    COMMIT;
END;
//

CREATE PROCEDURE ProcesarPalmaresGrupal (
    IN p_id_participacion INT,
    IN p_id_premio INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;
    INSERT INTO palmares_grupal (codigo_participacion, codigo_premio) 
    VALUES (p_id_participacion, p_id_premio);
    COMMIT;
END;
//
DELIMITER ;

-- 5. Funciones Almacenadas (2)
DELIMITER //
CREATE FUNCTION ObtenerTotalPremiosEquipo (p_id_equipo INT) 
RETURNS INT DETERMINISTIC
BEGIN
    DECLARE v_total INT;
    SELECT COUNT(*) INTO v_total 
    FROM palmares_grupal pg 
    INNER JOIN participaciones p ON pg.codigo_participacion = p.codigo_participacion 
    WHERE p.codigo_equipo = p_id_equipo;
    RETURN COALESCE(v_total, 0);
END;
//

CREATE FUNCTION ObtenerTorneosAtleta (p_id_atleta INT) 
RETURNS INT DETERMINISTIC
BEGIN
    DECLARE v_total INT;
    SELECT COUNT(DISTINCT part.codigo_torneo) INTO v_total 
    FROM detalles_participacion dp 
    INNER JOIN participaciones part ON dp.codigo_participacion = part.codigo_participacion 
    WHERE dp.codigo_atleta = p_id_atleta;
    RETURN COALESCE(v_total, 0);
END;
//
DELIMITER ;

-- 6. Subconsultas (2)
-- Subconsulta 1: Equipos con más de 3 premios
SELECT e.nombre 
FROM equipos e 
WHERE e.codigo_equipo IN (
    SELECT p.codigo_equipo 
    FROM participaciones p 
    INNER JOIN palmares_grupal pg ON p.codigo_participacion = pg.codigo_participacion 
    GROUP BY p.codigo_equipo 
    HAVING COUNT(*) > 3
);

-- Subconsulta 2: Atletas que han participado en torneos este año
SELECT a.p_nombre 
FROM atletas a 
WHERE a.codigo_atleta IN (
    SELECT dp.codigo_atleta 
    FROM detalles_participacion dp 
    INNER JOIN participaciones p ON dp.codigo_participacion = p.codigo_participacion 
    INNER JOIN torneos t ON p.codigo_torneo = t.codigo_torneo 
    WHERE YEAR(t.fecha_inicio) = YEAR(CURDATE())
);
