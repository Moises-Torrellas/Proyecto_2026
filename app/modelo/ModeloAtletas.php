<?php

namespace App\modelo;

use App\modelo\ModeloBase;
use Exception;

class ModeloAtletas extends ModeloBase
{
    private $id;
    private $doc_identidad;
    private $nombre;
    private $apellido;
    private $telefono;
    private $direccion;
    private $representante;
    private $posicion;
    private $genero;
    private $id_categoria;
    private $fecha_nac;
    private $foto;

    private $obj_posicion;
    private $obj_categoria;
    private $obj_representantes;


    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'cedula' => 'doc_identidad',
        ];
        $this->llavePrimaria = 'idUsuario';

        $this->obj_representantes = new ModeloRepresentantes;
        $this->obj_categoria = new ModeloCategorias;
        $this->obj_posicion = new ModeloPosiciones;
    }

    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            // Sentencia SQL unificada con herencia de datos y detalles de tablas maestras
            $sentencia = "SELECT 
                        a.*, 
                        COALESCE(a.telefono, r.telefono) AS telefono,
                        COALESCE(a.direccion, r.direccion) AS direccion,
                        r.nombre AS nombre_rep,
                        r.apellido AS apellido_rep,
                        r.cedula AS cedula_rep,
                        p.nombre AS nombre_posicion,
                        p.abreviatura AS abrev_posicion,
                        c.nombre AS nombre_categoria,
                        c.edad_min,
                        c.edad_max
                    FROM atletas a
                    LEFT JOIN representantes r ON a.id_representante = r.id_representante
                    LEFT JOIN posiciones p ON a.id_posicion = p.id_posicion
                    LEFT JOIN categorias c ON a.id_categoria = c.id_categorias
                    WHERE 1=1";

            // 1. BUSCADOR GENERAL (Filtro dinámico para el keyup)
            if (!empty($filtro['filtro'])) {
                $p = "%" . $filtro['filtro'] . "%";
                $sentencia .= " AND (
                a.doc_identidad LIKE :f1 OR 
                a.nombres LIKE :f2 OR 
                a.apellidos LIKE :f3 OR 
                r.cedula LIKE :f4 OR
                p.nombre LIKE :f5 OR
                c.nombre LIKE :f6
            )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
                $params[':f4'] = $p;
                $params[':f5'] = $p;
                $params[':f6'] = $p;
            }

            // 2. FILTROS ESPECÍFICOS (Por propiedades del objeto)
            if (!empty($this->doc_identidad)) {
                $sentencia .= " AND a.doc_identidad = :doc_i";
                $params[':doc_i'] = $this->doc_identidad;
            }

            if (!empty($this->id_categoria)) {
                $sentencia .= " AND a.id_categoria = :id_cat";
                $params[':id_cat'] = $this->id_categoria;
            }

            // 3. ORDENAMIENTO
            $sentencia .= " ORDER BY c.edad_min ASC, a.apellidos ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            // Registro del error en el log del sistema
            logs('Atletas', $e->getMessage(), 'Modelo_Consultar_Completo');
            return array('accion' => 'error', 'msg' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function ConsultarRepresentantes(){
        $respuesta = $this->obj_representantes->Consultar();
        $respuesta['accion'] = 'consultarR';
        return $respuesta;
    }
    public function ConsultarCategorias(){
        $respuesta = $this->obj_categoria->Consultar();
        $respuesta['accion'] = 'consultarC';
        return $respuesta;
    }
    public function ConsultarPosiciones(){
        $respuesta = $this->obj_posicion->Consultar();
        $respuesta['accion'] = 'consultarP';
        return $respuesta;
    }
}
