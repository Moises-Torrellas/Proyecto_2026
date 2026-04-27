<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;
use SensitiveParameter;

class ModeloCalidad extends ModeloBase
{
    private $id;
    private $nombre;
    private $nivel;

    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'id' => 'id_categorias',
            'nombre' => 'nombre'
        ];
        $this->llavePrimaria = 'id_categorias';
    }

    public function Consultar(array $filtro = []): array
{
    try {
        $conex = $this->conex();
        $params = []; 

        // 1. Sentencia base
        $sentencia = "SELECT * FROM estado_equipamiento WHERE 1=1";

        // 2. BUSCADOR GENERAL (Afecta a ambas columnas si se escribe algo en el input de búsqueda)
        if (!empty($filtro['filtro'])) {
            $p = "%" . $filtro['filtro'] . "%";
            // Cerramos el paréntesis del OR para que no choque con otros AND
            $sentencia .= " AND (nombre LIKE :f1 OR nivel_estado LIKE :f2)"; 
            $params[':f1'] = $p;
            $params[':f2'] = $p;
        }

        // 3. FILTROS ESPECÍFICOS (Por propiedades del objeto)
        // Filtro por Nombre
        if (!empty($this->nombre)) {
            $sentencia .= " AND nombre LIKE :nombre";
            $params[':nombre'] = trim($this->nombre) . "%";
        }

        // Filtro por Nivel de Estado
        if (!empty($this->nivel_estado)) {
            $sentencia .= " AND nivel_estado = :nivel";
            $params[':nivel'] = $this->nivel;
        }

        // 4. Orden (Ajustado a la tabla estado_equipamiento)
        // Cambié id_categorias por id, o la columna primaria que uses
        $sentencia .= " ORDER BY nombre ASC"; 

        $stmt = $conex->prepare($sentencia);
        $stmt->execute($params);

        $datos = $stmt->fetchAll();

        return array('accion' => 'consultar', 'datos' => $datos);

    } catch (Exception $e) {
        // Asegúrate de que la función logs() esté disponible
        logs('EstadoEquipamiento', $e->getMessage(), 'Modelo_Consultar');
        return array('accion' => 'error', 'mensaje' => 'Error al listar: ' . $e->getMessage());
    } finally {
        $conex = NULL;
    }
}
}