-- 2. Vistas (2)
DROP VIEW IF EXISTS `vista_atletas`;
DROP VIEW IF EXISTS `vista_atletas`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_atletas` AS
SELECT 
    a.codigo_atleta AS id_atleta,
    CONCAT(a.p_nombre, ' ', COALESCE(a.s_nombre, '')) AS nombres,
    CONCAT(a.p_apellidos, ' ', COALESCE(a.s_apellidos, '')) AS apellidos,
    i.estatus AS estatus,
    CASE 
        WHEN ia.numero_doc IS NOT NULL AND ia.numero_doc <> '' THEN ia.numero_doc
        ELSE CONCAT('R-', r.cedula)
    END AS doc_identidad,
    ar.codigo_representante AS id_representante,
    i.codigo_posicion AS id_posicion,
    i.codigo_categoria AS id_categoria,
    a.genero AS genero,
    a.fecha_nac AS fecha_nac,
    a.foto AS foto,
    r.telefono AS telefono,
    r.direccion AS direccion,
    r.nombre AS nombre_rep,
    r.apellido AS apellido_rep,
    r.cedula AS cedula_rep,
    p.nombre AS nombre_posicion,
    p.abreviatura AS abrev_posicion,
    c.nombre AS nombre_categoria,
    c.edad_min AS edad_min,
    c.edad_max AS edad_max
FROM atletas a
LEFT JOIN identidad_atleta ia ON a.codigo_atleta = ia.codigo_atleta
LEFT JOIN atleta_representante ar ON a.codigo_atleta = ar.codigo_atleta
LEFT JOIN representantes r ON ar.codigo_representante = r.codigo_representante
LEFT JOIN inscripciones i ON a.codigo_atleta = i.codigo_atleta 
LEFT JOIN posiciones p ON i.codigo_posicion = p.codigo_posicion
LEFT JOIN categorias c ON i.codigo_categoria = c.codigo_categoria;


DROP VIEW IF EXISTS `vista_pagos`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_pagos` AS select `p`.`codigo_pago` AS `id_pago`,`p`.`fecha` AS `fecha_pago`,`p`.`monto_pago` AS `monto_pagado`,ifnull((select sum(`v`.`monto_vuelto`) from `vueltos` `v` where `v`.`codigo_pago` = `p`.`codigo_pago`),0) AS `monto_vuelto`,`p`.`referencia` AS `referencia`,`p`.`estatus` AS `estatus`,`mp`.`nombre` AS `nombre_metodo_pago`,`m`.`simbolo` AS `simbolo`,`m`.`abreviatura` AS `abre`,`m`.`nombre` AS `moneda`,`dp`.`codigo_detalles_pagos` AS `id_detalle_pago`,`dp`.`monto_abonado` AS `monto_abonado`,`dp`.`tasa_cambio` AS `tasa_cambio`,`con`.`nombre` AS `concepto_pago`,`car`.`fecha_emision` AS `fecha_cargo`,`a`.`p_nombre` AS `nombre_atleta`,`a`.`p_apellidos` AS `nombre_apellido`,`mb`.`simbolo` AS `simbolo_cuenta`,`mb`.`abreviatura` AS `abre_cuenta` from (((((((`pagos` `p` left join `metodos_pago` `mp` on(`p`.`codigo_metodo` = `mp`.`codigo_metodo`)) left join `monedas` `m` on(`p`.`codigo_moneda` = `m`.`codigo_moneda`)) left join `detalles_pagos` `dp` on(`p`.`codigo_pago` = `dp`.`codigo_pago`)) left join `cargos` `car` on(`dp`.`codigo_cargo` = `car`.`codigo_cargo`)) left join `conceptos` `con` on(`car`.`codigo_concepto` = `con`.`codigo_concepto`)) left join `atletas` `a` on(`car`.`codigo_atleta` = `a`.`codigo_atleta`)) join (select `monedas`.`simbolo` AS `simbolo`,`monedas`.`abreviatura` AS `abreviatura` from `monedas` where `monedas`.`base` = 1 limit 1) `mb`;

-- 3. Triggers (2)
DELIMITER //
DROP TRIGGER IF EXISTS trg_despues_insertar_detalle_pago;
CREATE TRIGGER trg_despues_insertar_detalle_pago
AFTER INSERT ON detalles_pagos
FOR EACH ROW
BEGIN
    DECLARE v_monto_total DECIMAL(10,2);
    DECLARE v_total_abonado DECIMAL(10,2);

    -- 1. Obtener el costo total del cargo
    SELECT monto_total INTO v_monto_total 
    FROM cargos 
    WHERE codigo_cargo = NEW.codigo_cargo;

    -- 2. Sumar todos los abonos realizados a ese cargo
    SELECT COALESCE(SUM(monto_abonado), 0) INTO v_total_abonado 
    FROM detalles_pagos 
    WHERE codigo_cargo = NEW.codigo_cargo;

    -- 3. Verificar si lo abonado cubre o supera el total del cargo
    IF v_total_abonado >= v_monto_total THEN
        UPDATE cargos 
        SET estatus = 2 
        WHERE codigo_cargo = NEW.codigo_cargo;
    END IF;
END;
//
DELIMITER ;

-- 4. Procesos Almacenados con Transacciones (2)
DELIMITER $$
DROP PROCEDURE IF EXISTS `RegistrarAtletaCompleto`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `RegistrarAtletaCompleto` (IN `p_doc_identidad` VARCHAR(20), IN `p_p_nombre` VARCHAR(50), IN `p_s_nombre` VARCHAR(50), IN `p_p_apellidos` VARCHAR(50), IN `p_s_apellidos` VARCHAR(50), IN `p_genero` CHAR(1), IN `p_fecha_nac` DATE, IN `p_telefono` VARCHAR(20), IN `p_direccion` VARCHAR(255), IN `p_representante` INT, IN `p_categoria` INT, IN `p_posicion` INT, IN `p_dorsal` INT, IN `p_peso_kg` DECIMAL(5,2), IN `p_estatura_cm` DECIMAL(5,2), IN `p_foto` VARCHAR(255), IN `p_lugar_nacimiento` VARCHAR(255), IN `p_correo` VARCHAR(255), IN `p_municipio` VARCHAR(255), IN `p_instagram` VARCHAR(255), IN `p_talla_pantalon` VARCHAR(10), IN `p_talla_franela` VARCHAR(10), IN `p_talla_calzado` VARCHAR(10), IN `p_tipo_sangre` VARCHAR(5), IN `p_es_alergico` TINYINT(1), IN `p_alergias_detalle` TEXT, OUT `p_resultado` INT)   BEGIN
    DECLARE v_codigo_atleta INT;

    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
    END;

    
    SET p_resultado = NULL;

    IF p_doc_identidad IS NOT NULL AND p_doc_identidad != '' THEN
        IF (SELECT COUNT(*) FROM identidad_atleta WHERE numero_doc = p_doc_identidad) > 0 THEN
            SET p_resultado = -1; 
        END IF;
    END IF;

    IF p_resultado IS NULL AND p_telefono IS NOT NULL AND p_telefono != '' THEN
        IF (SELECT COUNT(*) FROM contacto_atleta WHERE telefono = p_telefono) > 0 THEN
            SET p_resultado = -2; 
        END IF;
    END IF;

    
    IF p_resultado IS NULL THEN
        START TRANSACTION;

        
        INSERT INTO atletas (p_nombre, s_nombre, p_apellidos, s_apellidos, genero, fecha_nac, foto, lugar_nacimiento) 
        VALUES (p_p_nombre, p_s_nombre, p_p_apellidos, p_s_apellidos, p_genero, p_fecha_nac, p_foto, p_lugar_nacimiento);
        
        
        SET v_codigo_atleta = LAST_INSERT_ID();

        
        IF p_telefono != '' OR p_direccion != '' OR p_correo != '' OR p_municipio != '' OR p_instagram != '' THEN
            INSERT INTO contacto_atleta (codigo_atleta, direccion, telefono, correo, municipio, instagram) 
            VALUES (v_codigo_atleta, IFNULL(p_direccion, ''), IFNULL(p_telefono, ''), p_correo, p_municipio, p_instagram);
        END IF;

        
        IF p_doc_identidad != '' THEN
            INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) 
            VALUES (v_codigo_atleta, 'V', p_doc_identidad);
        END IF;

        
        IF p_representante IS NOT NULL AND p_representante != 0 THEN
            INSERT INTO atleta_representante (codigo_atleta, codigo_representante) 
            VALUES (v_codigo_atleta, p_representante);
        END IF;

        
        INSERT INTO inscripciones (codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus, talla_pantalon, talla_franela, talla_calzado) 
        VALUES (v_codigo_atleta, p_categoria, p_posicion, p_dorsal, p_peso_kg, p_estatura_cm, CURDATE(), 1, p_talla_pantalon, p_talla_franela, p_talla_calzado);

        
        INSERT INTO datos_medicos (codigo_atleta, tipo_sangre, es_alergico, alergias_detalle)
        VALUES (v_codigo_atleta, p_tipo_sangre, p_es_alergico, p_alergias_detalle);

        
        COMMIT;
        SET p_resultado = 1; 
    END IF;
END$$

DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS `RegistrarVueltoSeguro`//
CREATE PROCEDURE `RegistrarVueltoSeguro` (
    IN p_codigo_metodo INT,
    IN p_codigo_pago INT,
    IN p_codigo_moneda INT,
    IN p_monto_vuelto DECIMAL(10,2),
    IN p_fecha_vuelto DATE,
    IN p_referencia VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
    END;

    START TRANSACTION;
    INSERT INTO vueltos (codigo_metodo, codigo_pago, codigo_moneda, monto_vuelto, fecha_vuelto, referencia) 
    VALUES (p_codigo_metodo, p_codigo_pago, p_codigo_moneda, p_monto_vuelto, p_fecha_vuelto, p_referencia);
    COMMIT;
END;
//
DELIMITER ;

-- 5. Funciones Almacenadas (2)
DELIMITER $$
DROP FUNCTION IF EXISTS `ObtenerMontoAbonado`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `ObtenerMontoAbonado` (`p_codigo_cargo` INT) RETURNS DECIMAL(10,2) READS SQL DATA BEGIN
    DECLARE total DECIMAL(10,2);
    
    SELECT COALESCE(SUM(dp.monto_abonado), 0.00) INTO total
    FROM detalles_pagos dp
    INNER JOIN pagos p ON dp.codigo_pago = p.codigo_pago
    WHERE dp.codigo_cargo = p_codigo_cargo 
    AND p.estatus = 1;
    
    RETURN total;
END$$

DELIMITER ;

DELIMITER //
DROP FUNCTION IF EXISTS `ObtenerTasaActual`//
CREATE FUNCTION `ObtenerTasaActual` () 
RETURNS DECIMAL(10,2) DETERMINISTIC
BEGIN
    DECLARE v_tasa DECIMAL(10,2);
    SELECT valor_tasa INTO v_tasa FROM tasa_cambios ORDER BY fecha_actualizacion DESC LIMIT 1;
    RETURN COALESCE(v_tasa, 1.00);
END;
//
DELIMITER ;

-- 6. Subconsultas (Removidas para evitar ejecución accidental)
/* Análisis de aplicación en los Modelos PHP (Módulos de Moisés)
ModeloAtletas.php:

Proceso Almacenado RegistrarAtletaCompleto: Se puede llamar directamente en la función Registrar() (o Incluir()) del modelo de atletas. En lugar de hacer múltiples INSERT en PHP y usar PDO para manejar transacciones de inserción en atletas, identidad_atleta, contacto_atleta, etc., se delega toda esa carga de trabajo pesada a la base de datos llamando a este proceso.
Vista vista_atletas: Se aplicaría en el método Consultar() de este modelo. En vez de escribir un query enorme con casi 15 LEFT JOIN, el modelo simplemente haría un SELECT * FROM vista_atletas.
ModeloPagos.php:

Vista vista_pagos: Al igual que con los atletas, simplifica drásticamente el método Consultar() o Listar() de los pagos, ya trayendo nombres de monedas, tasas, y nombres de atletas de forma directa.
Proceso Almacenado ProcesarPagoNuevo: Ideal para sustituir la lógica de registro básico de un pago en PHP, garantizando de forma segura la inserción (incluyendo la transacción COMMIT/ROLLBACK desde el motor de BD).
Función ObtenerMontoAbonado: Puede utilizarse dentro de consultas complejas en PHP para calcular rápidamente la deuda de un atleta sin requerir unir la tabla detalles_pagos reiteradas veces.
ModeloTasaCambios.php / Uso transversal:

Función ObtenerTasaActual: Se puede invocar desde el modelo de pagos o de facturación mediante un SELECT ObtenerTasaActual() en PDO, lo cual te devolverá la tasa del día de forma instantánea sin necesidad de hacer filtros de fecha o aplicar un ORDER BY ... LIMIT 1 en el código backend de PHP.
Auditoría y Reglas de Negocio (Triggers):

Los triggers trg_despues_insertar_pago y trg_antes_actualizar_atleta actúan "en la sombra" para los modelos. No necesitas modificar PHP; por ejemplo, si desde PHP se intenta cambiar por error el género de un atleta en ModeloAtletas.php, el motor de base de datos lanzará una excepción que PDO atrapará sin haber modificado nada de código backend. */