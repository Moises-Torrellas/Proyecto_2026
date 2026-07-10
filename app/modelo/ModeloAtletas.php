<?php

namespace App\modelo;

use Exception;
use PDO;

class ModeloAtletas extends Conexion
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
    private $categoria;
    private $fecha_nac;
    private $estatus;
    private $edad;
    private $foto;
    private $dorsal;
    private $peso_kg;
    private $estatura_cm;
    private $motivo_retiro;

    private $ObjCat;
    private $ObjRep;
    private $ObjPos;


    public function __construct()
    {
        parent::__construct();
        $this->campoWhitelist = [
            'telefono' => 'telefono',
            'categoria' => 'codigo_categoria',
            'posicion' => 'codigo_posicion',
            'representante' => 'codigo_representante',
            'id' => 'codigo_atleta'
        ];
        $this->llavePrimaria = 'codigo_atleta';
    }


    public function setModeloCategorias(ModeloCategorias $modeloCat)
    {
        $this->ObjCat = $modeloCat;
    }

    public function setModeloPosiciones(ModeloPosiciones $modeloPos)
    {
        $this->ObjPos = $modeloPos;
    }

    public function setModeloRepresentantes(ModeloRepresentantes $modeloRep)
    {
        $this->ObjRep = $modeloRep;
    }

    public function ProcesarDatos(array $datos): array
    {
        if (empty($datos)) {
            throw new Exception('No se proporcionaron datos para procesar el registro del atleta.');
        }

        $this->ValidarExpresiones($datos);

        $this->id            = $datos['id'] ?? null;
        $this->fecha_nac     = $datos['fecha_nac'] ?? '';
        $this->genero        = $datos['genero'] ?? '';
        $this->categoria     = $datos['categoria'] ?? null;
        $this->posicion      = $datos['posicion'] ?? null;
        $this->estatus       = $datos['estatus'] ?? null;
        $this->edad          = $datos['edad'] ?? null;
        $this->dorsal        = !empty($datos['dorsal']) ? $datos['dorsal'] : 0;
        $this->peso_kg       = !empty($datos['peso']) ? $datos['peso'] : 0.0;
        $this->estatura_cm   = !empty($datos['estatura']) ? $datos['estatura'] : 0;
        $this->motivo_retiro = $datos['motivo_retiro'] ?? 'Retiro Voluntario';

        $this->nombre   = mb_convert_case(trim($datos['nombre'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $this->apellido = mb_convert_case(trim($datos['apellido'] ?? ''), MB_CASE_TITLE, "UTF-8");

        $this->representante = $datos['representante'] ?? null;
        $this->doc_identidad = isset($datos['doc_identidad']) ? trim($datos['doc_identidad']) : null;
        $this->telefono = isset($datos['telefono']) ? trim($datos['telefono']) : null;
        $this->direccion     = isset($datos['direccion']) ?
            mb_convert_case(trim($datos['direccion']), MB_CASE_TITLE, "UTF-8") : null;

        if (isset($datos['foto']) && is_array($datos['foto'])) {
            $this->foto = $datos['foto'][0];
        } else {
            $this->foto = $datos['foto'] ?? 'default.png';
        }

        // 5. Ejecución de la acción vía Match
        $accion = $datos['accion'] ?? null;

        return match ($accion) {
            'incluir'   => $this->Incluir(),
            'modificar' => $this->Modificar(),
            'eliminar'  => $this->Eliminar(), // Retirar
            'reinscribir' => $this->Reinscribir(), // Nuevo estado
            'buscar'    => $this->Buscar(),
            'generar'   => $this->Consultar(),
            default     => throw new Exception('La acción solicitada para el atleta no es válida.')
        };
    }
    public function Consultar(array $filtro = []): array
    {
        try {
            $conex = $this->conex();
            $params = [];

            // 1. Apuntamos directamente a nuestra vista
            $sentencia = "SELECT * FROM vista_atletas WHERE 1=1";

            // 2. BUSCADOR GENERAL (Filtro dinámico para el keyup)
            if (!empty($filtro['filtro'])) {
                $p = "%" . trim($filtro['filtro']) . "%";

                // Agregamos las traducciones dinámicas con CASE WHEN para Género y Estatus
                $sentencia .= " AND (
                doc_identidad LIKE :f1 OR 
                nombres LIKE :f2 OR 
                apellidos LIKE :f3 OR 
                cedula_rep LIKE :f4 OR
                nombre_posicion LIKE :f5 OR
                nombre_categoria LIKE :f6 OR
                (CASE WHEN genero = 'M' THEN 'mujer' WHEN genero = 'H' THEN 'hombre' ELSE '' END) LIKE :f7 OR
                (CASE WHEN estatus = 1 THEN 'activo' WHEN estatus = 2 THEN 'retirado' ELSE '' END) LIKE :f8
            )";
                $params[':f1'] = $p;
                $params[':f2'] = $p;
                $params[':f3'] = $p;
                $params[':f4'] = $p;
                $params[':f5'] = $p;
                $params[':f6'] = $p;
                $params[':f7'] = $p;
                $params[':f8'] = $p;
            }

            // 3. FILTROS ESPECÍFICOS
            if (!empty($this->doc_identidad)) {
                $sentencia .= " AND doc_identidad LIKE :doc_i";
                $params[':doc_i'] = "__" . trim($this->doc_identidad) . "%";
            }
            if (!empty($this->nombre)) {
                $sentencia .= " AND nombres LIKE :nombre";
                $params[':nombre'] = '%' . trim($this->nombre) . "%";
            }
            if (!empty($this->apellido)) {
                $sentencia .= " AND apellidos LIKE :apellido";
                $params[':apellido'] = '%' . trim($this->apellido) . "%";
            }
            if (!empty($this->categoria)) {
                $sentencia .= " AND id_categoria = :id_cat";
                $params[':id_cat'] = $this->categoria;
            }
            if (!empty($this->posicion)) {
                $sentencia .= " AND id_posicion = :id_p";
                $params[':id_p'] = $this->posicion;
            }
            if (!empty($this->genero)) {
                $sentencia .= " AND genero = :genero";
                $params[':genero'] = $this->genero;
            }
            if (!empty($this->estatus)) {
                $sentencia .= " AND estatus = :estatus";
                $params[':estatus'] = $this->estatus;
            }
            if (!empty($this->edad)) {
                // Calculamos el año actual menos el año de nacimiento
                $sentencia .= " AND (YEAR(CURDATE()) - YEAR(fecha_nac)) = :edad";
                $params[':edad'] = (int)$this->edad;
            }
            // 4. ORDENAMIENTO
            $sentencia .= " ORDER BY CASE WHEN estatus = 1 THEN 0 ELSE 1 END ASC";

            $stmt = $conex->prepare($sentencia);
            $stmt->execute($params);
            $datos = $stmt->fetchAll();

            return array('accion' => 'consultar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Atletas', $e->getMessage(), 'Modelo_Consultar_Vista');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function ConsultarAtletas()
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $sentencia = "SELECT 
                            a.codigo_atleta,
                            a.p_nombre, 
                            a.p_apellidos,
                            a.s_nombre,
                            a.s_apellidos,
                            cat.nombre AS categoria,
                            CASE 
                                WHEN ia.numero_doc IS NOT NULL AND ia.numero_doc <> '' THEN ia.numero_doc
                                ELSE CONCAT('R-', r.cedula)
                            END AS documento_identidad
                            FROM atletas a
                            INNER JOIN inscripciones i ON a.codigo_atleta = i.codigo_atleta
                            INNER JOIN categorias cat ON i.codigo_categoria = cat.codigo_categoria
                            LEFT JOIN identidad_atleta ia ON a.codigo_atleta = ia.codigo_atleta
                            LEFT JOIN atleta_representante ar ON a.codigo_atleta = ar.codigo_atleta
                            LEFT JOIN representantes r ON ar.codigo_representante = r.codigo_representante
                            WHERE i.estatus = 1;";
            $stmt = $conex->prepare($sentencia);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'consultarA', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Atletas', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    private function Incluir()
    {
        $conex = null;

        // 1. Preparamos el arreglo con los datos nuevos (útil para el retorno o logs)
        $datos_nuevos = [
            'doc_identidad' => $this->doc_identidad,
            'nombre'        => $this->nombre,
            'apellido'      => $this->apellido,
            'genero'        => $this->genero,
            'fecha_nac'     => $this->fecha_nac,
            'telefono'      => $this->telefono,
            'direccion'     => $this->direccion,
            'representante' => $this->representante,
            'categoria'     => $this->categoria,
            'posicion'      => $this->posicion,
            'dorsal'        => $this->dorsal,
            'peso_kg'       => $this->peso_kg,
            'estatura_cm'   => $this->estatura_cm,
            'foto'          => $this->foto
        ];

        try {
            // Mantenemos las verificaciones de tus otros objetos en PHP
            if (!$this->ObjCat->verificarCategoria($this->categoria)) {
                throw new Exception(INVALID_ID);
            }
            if (!$this->ObjPos->verificarPosiciones($this->posicion)) {
                throw new Exception(INVALID_ID . '0');
            }
            if ($this->representante !== null && $this->representante !== '0') {
                if (!$this->ObjRep->verificarRepresentantes($this->representante)) {
                    throw new Exception(INVALID_ID . '1');
                }
            }

            // Separar nombres y apellidos antes de enviarlos a la BD
            $nombresArr  = explode(' ', trim($this->nombre), 2);
            $p_nombre    = $nombresArr[0];
            $s_nombre    = $nombresArr[1] ?? '';

            $apellidosArr = explode(' ', trim($this->apellido), 2);
            $p_apellidos  = $apellidosArr[0];
            $s_apellidos  = $apellidosArr[1] ?? '';

            $conex = $this->conex();

            // Llamamos al procedimiento almacenado pasando parámetros y declarando @resultado
            $sql = "CALL RegistrarAtletaCompleto(
            :doc, :pn, :sn, :pa, :sa, :gen, :fn, :tel, :dir, :rep, :cat, :pos, :dor, :peso, :est, :foto, @resultado
        )";

            $stmt = $conex->prepare($sql);
            $stmt->execute([
                ':doc'  => $this->doc_identidad ?? '',
                ':pn'   => $p_nombre,
                ':sn'   => $s_nombre,
                ':pa'   => $p_apellidos,
                ':sa'   => $s_apellidos,
                ':gen'  => $this->genero,
                ':fn'   => $this->fecha_nac,
                ':tel'  => $this->telefono ?? '',
                ':dir'  => $this->direccion ?? '',
                ':rep'  => ($this->representante !== '0' && $this->representante !== '') ? $this->representante : null,
                ':cat'  => $this->categoria,
                ':pos'  => $this->posicion,
                ':dor'  => $this->dorsal,
                ':peso' => $this->peso_kg,
                ':est'  => $this->estatura_cm,
                ':foto' => $this->foto
            ]);

            // ¡IMPORTANTE! Liberar el cursor para permitir ejecutar la siguiente consulta (SELECT @resultado)
            $stmt->closeCursor();

            // Consultamos qué nos respondió el Procedimiento Almacenado
            $resStmt = $conex->query("SELECT @resultado AS resultado");
            $row = $resStmt->fetch(PDO::FETCH_ASSOC);
            $resultado_sp = $row['resultado'];

            // Interpretamos los códigos devueltos por MySQL para lanzar las excepciones correspondientes
            if ($resultado_sp == -1) {
                throw new Exception(DUPLICATE_CEDULA);
            } elseif ($resultado_sp == -2) {
                throw new Exception(DUPLICATE_PHONE);
            } elseif ($resultado_sp == 0) {
                throw new Exception("Ocurrió un error en la base de datos al registrar el atleta.");
            }

            // Si llegamos aquí, el resultado fue 1 (Éxito)
            return array('accion' => 'exito', 'datos_nuevos' => json_encode($datos_nuevos));
        } catch (Exception $e) {
            // Ya no necesitas hacer $conex->rollBack() en PHP porque MySQL se encarga de eso.
            logs('Atletas', $e->getMessage(), 'Modelo_Incluir');
            return array('accion' => 'error', 'codigo' => $e->getMessage(), 'datos_nuevos' => json_encode($datos_nuevos));
        } finally {
            $conex = null;
        }
    }

    private function Modificar()
    {
        $conex = null;

        // 1. Preparamos el arreglo con los datos nuevos que se intentan guardar
        $datos_nuevos = [
            'doc_identidad' => $this->doc_identidad,
            'nombre'        => $this->nombre,
            'apellido'      => $this->apellido,
            'genero'        => $this->genero,
            'fecha_nac'     => $this->fecha_nac,
            'telefono'      => $this->telefono,
            'direccion'     => $this->direccion,
            'representante' => $this->representante,
            'categoria'     => $this->categoria,
            'posicion'      => $this->posicion,
            'dorsal'        => $this->dorsal,
            'peso_kg'       => $this->peso_kg,
            'estatura_cm'   => $this->estatura_cm,
            'foto'          => $this->foto
        ];

        try {
            if (!$this->verificarExistencia('categoria', $this->categoria, 'categorias', NULL)) {
                throw new Exception(INVALID_ID);
            }
            if (!$this->verificarExistencia('posicion', $this->posicion, 'posiciones', NULL)) {
                throw new Exception(INVALID_ID . '0');
            }
            if ($this->representante !== null) {
                if (!$this->verificarExistencia('representante', $this->representante, 'representantes', NULL)) {
                    throw new Exception(INVALID_ID . '1');
                }
            }

            $conex = $this->conex();
            $conex->beginTransaction();

            if ($this->doc_identidad !== null && $this->doc_identidad !== '') {
                $stmtVerifDoc = $conex->prepare("SELECT COUNT(*) FROM identidad_atleta WHERE numero_doc = :doc AND codigo_atleta != :id");
                $stmtVerifDoc->execute([':doc' => $this->doc_identidad, ':id' => $this->id]);
                if ($stmtVerifDoc->fetchColumn() > 0) {
                    throw new Exception(DUPLICATE_CEDULA);
                }
            }

            if ($this->telefono !== null && $this->telefono !== '') {
                $stmtVerifTel = $conex->prepare("SELECT COUNT(*) FROM contacto_atleta WHERE telefono = :tel AND codigo_atleta != :id");
                $stmtVerifTel->execute([':tel' => $this->telefono, ':id' => $this->id]);
                if ($stmtVerifTel->fetchColumn() > 0) {
                    throw new Exception(DUPLICATE_PHONE);
                }
            }

            // Separar nombres y apellidos
            $nombresArr = explode(' ', $this->nombre, 2);
            $p_nombre = $nombresArr[0];
            $s_nombre = isset($nombresArr[1]) ? $nombresArr[1] : '';

            $apellidosArr = explode(' ', $this->apellido, 2);
            $p_apellidos = $apellidosArr[0];
            $s_apellidos = isset($apellidosArr[1]) ? $apellidosArr[1] : '';

            $sqlAtleta = "UPDATE atletas SET p_nombre = :pn, s_nombre = :sn, p_apellidos = :pa, s_apellidos = :sa, 
                      genero = :gen, fecha_nac = :fn, foto = :foto WHERE codigo_atleta = :id";
            $stmtAtleta = $conex->prepare($sqlAtleta);
            $stmtAtleta->execute([
                ':pn' => $p_nombre,
                ':sn' => $s_nombre,
                ':pa' => $p_apellidos,
                ':sa' => $s_apellidos,
                ':gen' => $this->genero,
                ':fn' => $this->fecha_nac,
                ':foto' => $this->foto,
                ':id' => $this->id
            ]);

            // Update contacto
            $stmtContacto = $conex->prepare("SELECT COUNT(*) FROM contacto_atleta WHERE codigo_atleta = :id");
            $stmtContacto->execute([':id' => $this->id]);
            if ($stmtContacto->fetchColumn() > 0) {
                $sqlC = "UPDATE contacto_atleta SET direccion = :dir, telefono = :tel WHERE codigo_atleta = :id";
                $stmtC = $conex->prepare($sqlC);
                $stmtC->execute([':dir' => $this->direccion ?? '', ':tel' => $this->telefono ?? '', ':id' => $this->id]);
            } else {
                if ($this->telefono || $this->direccion) {
                    $sqlC = "INSERT INTO contacto_atleta (codigo_atleta, direccion, telefono) VALUES (:ca, :dir, :tel)";
                    $stmtC = $conex->prepare($sqlC);
                    $stmtC->execute([':ca' => $this->id, ':dir' => $this->direccion ?? '', ':tel' => $this->telefono ?? '']);
                }
            }

            // Update identidad
            if ($this->doc_identidad) {
                $stmtIdent = $conex->prepare("SELECT COUNT(*) FROM identidad_atleta WHERE codigo_atleta = :id");
                $stmtIdent->execute([':id' => $this->id]);
                if ($stmtIdent->fetchColumn() > 0) {
                    $sqlIdentidad = "UPDATE identidad_atleta SET numero_doc = :nd WHERE codigo_atleta = :id";
                    $stmtIdentidad = $conex->prepare($sqlIdentidad);
                    $stmtIdentidad->execute([':nd' => $this->doc_identidad, ':id' => $this->id]);
                } else {
                    $sqlIdentidad = "INSERT INTO identidad_atleta (codigo_atleta, tipo_doc, numero_doc) VALUES (:id, 'V', :nd)";
                    $stmtIdentidad = $conex->prepare($sqlIdentidad);
                    $stmtIdentidad->execute([':nd' => $this->doc_identidad, ':id' => $this->id]);
                }
            }

            // Update atleta_representante
            if ($this->representante && $this->representante !== '0') {
                $stmtRep = $conex->prepare("SELECT COUNT(*) FROM atleta_representante WHERE codigo_atleta = :id");
                $stmtRep->execute([':id' => $this->id]);
                if ($stmtRep->fetchColumn() > 0) {
                    $sqlAr = "UPDATE atleta_representante SET codigo_representante = :cr WHERE codigo_atleta = :id";
                    $stmtAr = $conex->prepare($sqlAr);
                    $stmtAr->execute([':cr' => $this->representante, ':id' => $this->id]);
                } else {
                    $sqlAr = "INSERT INTO atleta_representante (codigo_atleta, codigo_representante) VALUES (:id, :cr)";
                    $stmtAr = $conex->prepare($sqlAr);
                    $stmtAr->execute([':cr' => $this->representante, ':id' => $this->id]);
                }
            }

            // Update inscripciones (the latest one)
            $sqlInsc = "UPDATE inscripciones SET codigo_categoria = :cc, codigo_posicion = :cp, dorsal = :dorsal, peso_kg = :peso, estatura_cm = :estatura 
                    WHERE codigo_atleta = :id AND codigo_inscripcion = (SELECT max_id FROM (SELECT MAX(codigo_inscripcion) as max_id FROM inscripciones WHERE codigo_atleta = :id2) AS temp)";
            $stmtInsc = $conex->prepare($sqlInsc);
            $stmtInsc->execute([
                ':cc' => $this->categoria,
                ':cp' => $this->posicion,
                ':dorsal' => $this->dorsal,
                ':peso' => $this->peso_kg,
                ':estatura' => $this->estatura_cm,
                ':id' => $this->id,
                ':id2' => $this->id
            ]);

            $conex->commit();

            // 2. Retornamos los datos nuevos en caso de éxito
            return array('accion' => 'exito', 'datos_nuevos' => json_encode($datos_nuevos));
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Atletas', $e->getMessage(), 'Modelo_Modificar');

            // 3. Retornamos los datos nuevos en caso de error
            return array('accion' => 'error', 'codigo' => $e->getMessage(), 'datos_nuevos' => json_encode($datos_nuevos));
        } finally {
            $conex = null;
        }
    }

    public function Buscar($id = null)
    {
        $conex = null;
        try {
            $codigo = ($id === null) ? $this->id : $id;
            $conex = $this->conex();
            $sentencia = "SELECT * FROM vista_atletas WHERE id_atleta = :id";
            $stmt = $conex->prepare($sentencia);
            $stmt->bindParam(':id', $codigo);
            $stmt->execute();
            $datos = $stmt->fetchAll();
            return array('accion' => 'buscar', 'datos' => $datos);
        } catch (Exception $e) {
            logs('Atletas', $e->getMessage(), 'Modelo');
            return array('accion' => 'error', 'mensaje' => $e->getMessage());
        } finally {
            $conex = NULL;
        }
    }

    public function ConsultarCumple()
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $sql = "SELECT p_nombre as nombres, p_apellidos as apellidos FROM atletas 
                WHERE MONTH(fecha_nac) = MONTH(NOW()) 
                AND DAY(fecha_nac) = DAY(NOW())";
            return $conex->query($sql)->fetchAll();
        } catch (Exception $e) {
            logs('Atletas', $e->getMessage(), 'Modelo_ConsultarCumple');
            return [];
        }
    }

    private function Eliminar(): array
    {
        $conex = null;
        try {
            $conex = $this->conex();
            $conex->beginTransaction();
            $stmtVerif = $conex->prepare("SELECT COUNT(*) FROM atletas WHERE codigo_atleta = :id");
            $stmtVerif->execute([':id' => $this->id]);
            if ($stmtVerif->fetchColumn() == 0) {
                throw new Exception(INVALID_ID);
            }

            $stmtCargo = $conex->prepare("SELECT COUNT(*) FROM cargos WHERE codigo_atleta = :id AND estatus = 1");
            $stmtCargo->execute([':id' => $this->id]);

            if ($stmtCargo->fetchColumn() > 0) {
                throw new Exception(ASSOCIATES);
            }
            $stmtCargo = $conex->prepare("SELECT COUNT(*) FROM asignaciones WHERE codigo_atleta = :id AND estatus = 1");
            $stmtCargo->execute([':id' => $this->id]);

            if ($stmtCargo->fetchColumn() > 0) {
                throw new Exception(ASSOCIATES . '1');
            }

            // Cambiar estatus de la inscripcion a 2
            $sql = "UPDATE inscripciones SET estatus = 2 WHERE codigo_atleta = :id AND codigo_inscripcion = (SELECT max_id FROM (SELECT MAX(codigo_inscripcion) as max_id FROM inscripciones WHERE codigo_atleta = :id2) AS temp)";
            $stmt = $conex->prepare($sql);
            $stmt->execute([':id' => $this->id, ':id2' => $this->id]);

            // Obtener el ID de esa ultima inscripcion para insertar el retiro
            $sqlMax = "SELECT MAX(codigo_inscripcion) FROM inscripciones WHERE codigo_atleta = :id";
            $stmtMax = $conex->prepare($sqlMax);
            $stmtMax->execute([':id' => $this->id]);
            $cod_insc = $stmtMax->fetchColumn();

            $sqlRetiro = "INSERT INTO retiros (codigo_inscripcion, fecha_retiro, motivo) VALUES (:ci, CURDATE(), :motivo)";
            $stmtRetiro = $conex->prepare($sqlRetiro);
            $stmtRetiro->execute([':ci' => $cod_insc, ':motivo' => $this->motivo_retiro]);

            $conex->commit();
            return ['accion' => 'exito'];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Atletas', $e->getMessage(), 'Modelo_Eliminar');
            return ['accion' => 'error', 'codigo' => $e->getMessage()];
        } finally {
            $conex = null;
        }
    }

    private function Reinscribir(): array
    {
        $conex = null;

        // 1. Capturamos los datos de la nueva inscripción
        $datos_nuevos = [
            'categoria'   => $this->categoria,
            'posicion'    => $this->posicion,
            'dorsal'      => $this->dorsal,
            'peso_kg'     => $this->peso_kg,
            'estatura_cm' => $this->estatura_cm
        ];

        try {
            $conex = $this->conex();
            $conex->beginTransaction();

            $sqlInsc = "INSERT INTO inscripciones (codigo_atleta, codigo_categoria, codigo_posicion, dorsal, peso_kg, estatura_cm, fecha_inscripcion, estatus) 
                    VALUES (:ca, :cc, :cp, :dorsal, :peso, :estatura, CURDATE(), 1)";
            $stmtInsc = $conex->prepare($sqlInsc);
            $stmtInsc->execute([
                ':ca'       => $this->id,
                ':cc'       => $this->categoria,
                ':cp'       => $this->posicion,
                ':dorsal'   => $this->dorsal,
                ':peso'     => $this->peso_kg,
                ':estatura' => $this->estatura_cm
            ]);

            $conex->commit();

            // 2. Retornamos los datos nuevos en el éxito
            return ['accion' => 'exito', 'datos_nuevos' => json_encode($datos_nuevos)];
        } catch (Exception $e) {
            if ($conex && $conex->inTransaction()) {
                $conex->rollBack();
            }
            logs('Atletas', $e->getMessage(), 'Modelo_Reinscribir');

            // 3. Retornamos los datos nuevos en el error
            return ['accion' => 'error', 'codigo' => $e->getMessage(), 'datos_nuevos' => json_encode($datos_nuevos)];
        } finally {
            $conex = null;
        }
    }

    private function ValidarExpresiones(array $datos): void
    {
        $accion = $datos['accion'] ?? '';

        if (!empty($datos['id']) && !preg_match('/^[0-9]+$/', $datos['id'])) {
            throw new Exception('Id inválido.');
        }
        if (!empty($datos['fecha_nac']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha_nac'])) {
            throw new Exception('Formato de fecha inválido. Use AAAA-MM-DD.');
        }
        if (!empty($datos['nombre'])) {
            $regla = ($accion === 'generar') ? '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,60}$/' : '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,60}$/';
            if (!preg_match($regla, $datos['nombre'])) throw new Exception('Nombres inválido.');
        }
        if (!empty($datos['apellido'])) {
            $regla = ($accion === 'generar') ? '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{1,60}$/' : '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,60}$/';
            if (!preg_match($regla, $datos['apellido'])) throw new Exception('Apellidos inválido.');
        }
        if (!empty($datos['categoria']) && !preg_match('/^[0-9]+$/', $datos['categoria'])) {
            throw new Exception('Categoria o categoria inválida.');
        }
        if (!empty($datos['posicion']) && !preg_match('/^[0-9]+$/', $datos['posicion'])) {
            throw new Exception('Posición o posicion inválida.');
        }
        if (!empty($datos['genero']) && !preg_match('/^[HM]$/', $datos['genero'])) {
            throw new Exception('Genero o genero inválido.');
        }
        if (!empty($datos['representante']) && !preg_match('/^[1-9]+$/', $datos['representante'])) {
            throw new Exception('Representante inválido.');
        }
        if (!empty($datos['doc_identidad'])) {
            $regla_doc = ($accion === 'generar') ? '/^[0-9]{1,8}$/' : '/^[0-9]{7,8}$/';
            $mensaje_doc = ($accion === 'generar') ? 'documento de identidad inválido.' : 'Cedula inválida. Debe contener de 7 a 8 dígitos.';
            if (!preg_match($regla_doc, $datos['doc_identidad'])) throw new Exception($mensaje_doc);
        }
        if (!empty($datos['telefono']) && !preg_match('/^[0-9]{4}[-]{1}[0-9]{7}$/', $datos['telefono'])) {
            throw new Exception('Telefono invalido.');
        }
        if (!empty($datos['direccion']) && !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{3,150}$/', $datos['direccion'])) {
            throw new Exception('Direccion inválida.');
        }
        if (!empty($datos['foto_actual']) && !preg_match('/^atleta_\d{4}-\d{2}-\d{2}_\d+\.(png|jpg|jpeg|webp)$/', $datos['foto_actual'])) {
            throw new Exception('El nombre de la foto tiene un formato inválido o una extensión no permitida.');
        }
        if (!empty($datos['edad']) && !preg_match('/^[0-9]{1,2}$/', $datos['edad'])) {
            throw new Exception('Edad inválida.');
        }
        if (!empty($datos['estatus']) && !preg_match('/^[1-2]$/', $datos['estatus'])) {
            throw new Exception('estatus inválido.');
        }
        if (!empty($datos['dorsal']) && !preg_match('/^[0-9]{1,3}$/', $datos['dorsal'])) {
            throw new Exception('Dorsal inválido.');
        }
        if (!empty($datos['peso']) && !preg_match('/^[0-9]+(\.[0-9]{1,2})?$/', $datos['peso'])) {
            throw new Exception('Peso inválido.');
        }
        if (!empty($datos['estatura']) && !preg_match('/^[0-9]{2,3}$/', $datos['estatura'])) {
            throw new Exception('Estatura inválida.');
        }

        if ($accion === 'incluir' || $accion === 'modificar' || $accion === 'reinscribir') {
            if (!empty($datos['fecha_nac'])) {
                $fecha_nac = $datos['fecha_nac'];
                $anio_nac = (int)date('Y', strtotime($fecha_nac));
                $anio_act = (int)date('Y');
                $edad_cal = $anio_act - $anio_nac;
                if ($edad_cal < 18) {
                    if (empty($datos['representante']) || $datos['representante'] == "0") {
                        throw new Exception('El atleta es menor de edad necesita asociar un representante.');
                    }
                }
                if ($edad_cal > 9) {
                    if (empty($datos['doc_identidad'])) {
                        throw new Exception('Necesita ingresar el documento de identidad del atleta.');
                    }
                }
            }
        }
    }
}
