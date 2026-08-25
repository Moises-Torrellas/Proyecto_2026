--Ajustes BD Yonathan

-- Índice para el Catalogo
CREATE INDEX idx_catalogo_busqueda ON catalogo(nombre, talla);
CREATE INDEX idx_catalogo_categoria ON catalogo(id_categoria);

-- Índice para el Inventario Físico
CREATE INDEX idx_inventario_club ON articulos_inventario(codigo_club);
CREATE INDEX idx_inventario_estatus ON articulos_inventario(estatus, id_estado);

-- Vista Asignaciones
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

--Funcion almacenada Calcular Stock Disponible (Catálogo)
DELIMITER $$
CREATE FUNCTION StockDisponibleCatalogo(p_id_catalogo INT) 
RETURNS INT READS SQL DATA
BEGIN
    DECLARE v_total INT;
    -- Cuenta cuántos artículos físicos de ese catálogo están libres (estatus 1) y en excelente estado (id_estado 1)
    SELECT COUNT(*) INTO v_total FROM articulos_inventario 
    WHERE id_catalogo = p_id_catalogo AND estatus = 1 AND id_estado = 1;
    RETURN v_total;
END$$
DELIMITER ;

--Función almacenada para determinar si un artículo es apto para uso)

DELIMITER $$
CREATE FUNCTION EsAptoParaUso(p_id_estado INT) 
RETURNS TINYINT READS SQL DATA
BEGIN
    DECLARE v_nivel TINYINT;
    SELECT nivel_estado INTO v_nivel FROM estado_fisico WHERE id_estado = p_id_estado;
    -- Si el nivel del estado es 1 (Excelente), retorna 1 (Sí). Si no, retorna 0 (No).
    RETURN IF(v_nivel = 1, 1, 0);
END$$
DELIMITER ;

-- Trigger para bloquear articulos dañados ModeloArticulosInventario

DELIMITER $$
CREATE TRIGGER trg_bloquear_articulos_danados
BEFORE UPDATE ON articulos_inventario
FOR EACH ROW
BEGIN
    -- Si el nuevo estado físico es 2 (Dañado)
    IF NEW.id_estado = 2 THEN
        -- Lo pasamos a estatus 3 (Retirado) para que no salga en la lista de disponibles
        SET NEW.estatus = 3;
    END IF;
END$$
DELIMITER ;

-- Procedimiento almacenado y transaccion inventario de articulos ModeloArticulosInventario
DELIMITER $$
CREATE PROCEDURE RetirarArticuloSeguro(
    IN p_articulo INT,
    OUT p_resultado INT
)
BEGIN
    DECLARE v_estatus TINYINT;

    -- Manejador de errores: Si la base de datos falla, se hace ROLLBACK automático
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0; -- 0: Error de BD
    END;

    -- Iniciamos la transacción segura
    START TRANSACTION;

    -- Consultamos y bloqueamos el registro momentáneamente
    SELECT estatus INTO v_estatus FROM articulos_inventario WHERE codigo_articulo = p_articulo FOR UPDATE;

    IF v_estatus IS NULL THEN
        SET p_resultado = -1; -- -1: El equipo no existe
        ROLLBACK;
    ELSEIF v_estatus != 1 THEN
        SET p_resultado = -2; -- -2: El equipo está en uso o ya fue retirado
        ROLLBACK;
    ELSE
        -- Retiramos el artículo (estatus 3 según tu lógica)
        UPDATE articulos_inventario SET estatus = 3 WHERE codigo_articulo = p_articulo;
        
        COMMIT; -- Guardamos cambios
        SET p_resultado = 1; -- 1: Éxito
    END IF;
END$$
DELIMITER ;

-- Procedimiento almacenado con transaccion catalogo

DELIMITER $$
CREATE PROCEDURE EliminarCatalogoSeguro(
    IN p_id_catalogo INT,
    OUT p_resultado INT
)
BEGIN
    DECLARE v_existe INT;
    DECLARE v_en_uso INT;

    -- Si hay un fallo de integridad, se deshace todo
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0; 
    END;

    START TRANSACTION;

    -- Validar si el catálogo existe
    SELECT COUNT(*) INTO v_existe FROM catalogo WHERE id_catalogo = p_id_catalogo FOR UPDATE;
    
    -- Validar si tiene artículos físicos amarrados en el inventario
    SELECT COUNT(*) INTO v_en_uso FROM articulos_inventario WHERE id_catalogo = p_id_catalogo;

    IF v_existe = 0 THEN
        SET p_resultado = -1; -- -1: No existe
        ROLLBACK;
    ELSEIF v_en_uso > 0 THEN
        SET p_resultado = -2; -- -2: No se puede borrar, tiene artículos físicos
        ROLLBACK;
    ELSE
        -- Todo en orden, procedemos a eliminar
        DELETE FROM catalogo WHERE id_catalogo = p_id_catalogo;
        COMMIT;
        SET p_resultado = 1; -- 1: Éxito
    END IF;
END$$
DELIMITER ;

-- Subconsulta para obtener articulos libres en ModeloArticulosInventario
/*
public function ConsultarArticulosLibres(): array { 
        $conex = null;
        try {
            $conex = $this->conex();
            
            // APLICACIÓN DE SUBCONSULTA: Extraemos el nombre del catálogo consultando la tabla anidada
            $sql = "SELECT e.codigo_articulo, 
                           e.codigo_club, 
                           IFNULL((SELECT nombre FROM catalogo c WHERE c.id_catalogo = e.id_catalogo), 'Artículo sin registrar') as articulo 
                    FROM articulos_inventario e 
                    WHERE e.estatus = 1";
                    
            $articulos = $conex->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            return ['accion' => 'exito', 'datos' => $articulos];
        } catch (Exception $e) {
            return ['accion' => 'error', 'datos' => []];
        }
    }
*/ 

-- Subconsulta para obtener el nombre de la categoría sin usar INNER JOIN en ModeloCatalogo
/*
public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            // SUBCONSULTA 1: Para obtener el nombre de la categoría sin usar INNER JOIN
            $sentencia = "SELECT c.*, 
                                 (SELECT cat.nombre FROM categoria_catalogo cat WHERE cat.id_categoria = c.id_categoria) as categoria_nombre,
                                 StockDisponibleCatalogo(c.id_catalogo) AS stock_actual
                          FROM catalogo c
                          WHERE 1=1"; 
            
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                // SUBCONSULTA 2: Para buscar coincidencias dentro de la tabla de categorías
                $sentencia .= " AND (
                    c.nombre LIKE :f1 OR 
                    c.talla LIKE :f3 OR
                    c.id_categoria IN (SELECT id_categoria FROM categoria_catalogo WHERE nombre LIKE :f2)
                )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
            }

            if (!empty($this->id_categoria)) {
                $sentencia .= " AND c.id_categoria = :id_categoria";
                $params[':id_categoria'] = $this->id_categoria;
            }

            if (!empty($this->talla)) {
                $sentencia .= " AND c.talla = :talla";
                $params[':talla'] = $this->talla;
            }

            $sentencia .= " ORDER BY c.nombre ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Catalogo', $e->getMessage(), 'Modelo_Consultar');
            return array('accion' => 'error');
        } finally {
            $conex = NULL;
        }
    }
*/