import os

lines = open('c:/xampp/htdocs/Proyecto_2026/cannibalsbd2.sql', 'r', encoding='utf-8').readlines()

def get_block(start_line, end_char):
    block = []
    for line in lines[start_line:]:
        block.append(line)
        if end_char in line:
            break
    return "".join(block)

proc = get_block(64, 'END$$')
func = get_block(175, 'END$$')
va = get_block(1138, ';')
vp = get_block(1158, ';')

content = """-- ==========================================================
-- Archivo SQL para: Moisés Torrellas
-- Módulos: Atleta, Representante, Posición, Pagos, Categoría de Catálogo, Estado Físico, Tasa De Cambio
-- ==========================================================

-- 1. Índices (2)
CREATE INDEX idx_atletas_nombre ON atletas (p_nombre, p_apellidos);
CREATE INDEX idx_pagos_fecha ON pagos (fecha);

-- 2. Vistas (2)
DROP VIEW IF EXISTS `vista_atletas`;
{va}

DROP VIEW IF EXISTS `vista_pagos`;
{vp}

-- 3. Triggers (2)
DELIMITER //
CREATE TRIGGER trg_despues_insertar_pago
AFTER INSERT ON pagos
FOR EACH ROW
BEGIN
    INSERT INTO auditoria_pagos (id_pago, fecha_registro) VALUES (NEW.codigo_pago, NOW());
END;
//

CREATE TRIGGER trg_antes_actualizar_atleta
BEFORE UPDATE ON atletas
FOR EACH ROW
BEGIN
    IF OLD.genero != NEW.genero THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede modificar el género del atleta';
    END IF;
END;
//
DELIMITER ;

-- 4. Procesos Almacenados con Transacciones (2)
DELIMITER $$
{proc}
DELIMITER ;

DELIMITER //
CREATE PROCEDURE `ProcesarPagoNuevo` (
    IN p_id_atleta INT, 
    IN p_monto DECIMAL(10,2), 
    IN p_id_tasa INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;
    INSERT INTO pagos (codigo_atleta, monto_pago, fecha, codigo_tasa) 
    VALUES (p_id_atleta, p_monto, CURDATE(), p_id_tasa);
    COMMIT;
END;
//
DELIMITER ;

-- 5. Funciones Almacenadas (2)
DELIMITER $$
{func}
DELIMITER ;

DELIMITER //
CREATE FUNCTION `ObtenerTasaActual` () 
RETURNS DECIMAL(10,2) DETERMINISTIC
BEGIN
    DECLARE v_tasa DECIMAL(10,2);
    SELECT valor_tasa INTO v_tasa FROM tasa_cambios ORDER BY fecha_actualizacion DESC LIMIT 1;
    RETURN COALESCE(v_tasa, 1.00);
END;
//
DELIMITER ;

-- 6. Subconsultas (2)
-- Subconsulta 1: Atletas sin pagos registrados
SELECT p_nombre, p_apellidos 
FROM atletas 
WHERE codigo_atleta NOT IN (SELECT codigo_atleta FROM pagos);

-- Subconsulta 2: Último pago realizado por cada atleta
SELECT a.p_nombre, 
       (SELECT MAX(fecha) FROM pagos p WHERE p.codigo_atleta = a.codigo_atleta) AS ultimo_pago
FROM atletas a;
""".replace('{va}', va).replace('{vp}', vp).replace('{proc}', proc).replace('{func}', func)

with open('c:/xampp/htdocs/Proyecto_2026/moises_torrellas_objetos.sql', 'w', encoding='utf-8') as f:
    f.write(content)

print("Moises file updated!")
