-- 2. Vistas (2)
DROP VIEW IF EXISTS `vista_atletas`;
DROP VIEW IF EXISTS `vista_atletas`;


DROP VIEW IF EXISTS `vista_pagos`;
DROP VIEW IF EXISTS `vista_pagos`;


-- 3. Triggers (2)
DELIMITER //
DROP TRIGGER IF EXISTS trg_antes_insertar_pago;
CREATE TRIGGER trg_antes_insertar_pago
BEFORE INSERT ON pagos
FOR EACH ROW
BEGIN
    IF NEW.monto_pago <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El monto del pago debe ser mayor a cero';
    END IF;
END;
//

DROP TRIGGER IF EXISTS trg_antes_actualizar_atleta;
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