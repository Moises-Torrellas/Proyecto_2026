<?php
// Declaramos el espacio de nombres de la prueba dentro del paquete de pruebas unitarias de Pagos
namespace tests\Unit\Pagos;

// Importamos la clase base TestCase provista por el framework PHPUnit
use PHPUnit\Framework\TestCase;
// Importamos la clase ModeloPagos para evaluar las operaciones del registro de pagos
use App\modelo\ModeloPagos;
// Importamos la clase abstracta Conexion para gestionar las conexiones simuladas
use App\modelo\Conexion;
// Importamos la clase Exception nativa para aserciones de excepciones
use Exception;
// Importamos la clase nativa PDO para crear el Mock del gestor de base de datos
use PDO;
// Importamos la clase nativa PDOStatement para crear el Mock de las consultas preparadas
use PDOStatement;
use PHPUnit\Framework\Attributes\Group;
// Importamos ReflectionClass para poder acceder e inyectar el mock en la propiedad estática privada de Conexion
use ReflectionClass;

// Verificamos si la constante EMPTY_SELECTION se encuentra definida en el entorno
if (!defined('EMPTY_SELECTION')) {
    // Si no está definida, requerimos el archivo de configuración global del proyecto
    require_once dirname(__DIR__, 3) . '/config/config.php';
}

// Definimos la clase de pruebas unitarias para el registro de pagos heredando de TestCase
#[Group('unit')]
class RegistrarPagosTest extends TestCase
{
    

    // Caso 1: Verificar que ProcesarDatos lance una excepción si se le envía un arreglo vacío
    public function testProcesarDatosLanzaExcepcionCuandoLosDatosEstanVacios(): void
    {
        // 1. Arrange: Instanciamos el modelo de pagos
        $modelo = new ModeloPagos();
        // Definimos un arreglo vacío sin datos
        $datosVacios = [];

        // Indicamos a PHPUnit que esperamos recibir una instancia de Exception
        $this->expectException(Exception::class);
        // Indicamos el mensaje exacto que debe contener la excepción lanzada
        $this->expectExceptionMessage('No se proporcionaron datos para procesar.');

        // 2. Act: Ejecutamos el método con los datos vacíos esperando el lanzamiento de la excepción
        $modelo->ProcesarDatos($datosVacios);
    }

    // Caso 2: Verificar que ProcesarDatos lance una excepción si el monto tiene formato no numérico
    public function testProcesarDatosLanzaExcepcionCuandoMontoTieneFormatoInvalido(): void
    {
        // 1. Arrange: Instanciamos el modelo de pagos
        $modelo = new ModeloPagos();
        // Preparamos datos con un monto en texto no numérico
        $datosInvalidos = [
            'monto' => 'monto_invalido'
        ];

        // Indicamos a PHPUnit que esperamos una excepción
        $this->expectException(Exception::class);
        // Indicamos el mensaje de validación de formato numérico de monto
        $this->expectExceptionMessage('Monto del pago inválido.');

        // 2. Act: Ejecutamos el método esperando la falla de validación por expresión regular
        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 3: Verificar que ProcesarDatos lance una excepción si la referencia no tiene entre 4 y 12 dígitos
    public function testProcesarDatosLanzaExcepcionCuandoReferenciaTieneFormatoInvalido(): void
    {
        // 1. Arrange: Instanciamos el modelo de pagos
        $modelo = new ModeloPagos();
        // Preparamos datos con una referencia de solo 2 dígitos
        $datosInvalidos = [
            'referencia' => '12'
        ];

        // Indicamos a PHPUnit que esperamos una excepción
        $this->expectException(Exception::class);
        // Indicamos el mensaje de validación esperado para la referencia
        $this->expectExceptionMessage('La referencia del pago debe tener entre 4 y 12 dígitos numéricos.');

        // 2. Act: Ejecutamos la validación
        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 4: Verificar que se lance una excepción si se intenta registrar un pago con fecha futura
    public function testProcesarDatosLanzaExcepcionCuandoFechaEsFutura(): void
    {
        // 1. Arrange: Instanciamos el modelo de pagos
        $modelo = new ModeloPagos();
        // Preparamos datos con una fecha en el futuro
        $datosInvalidos = [
            'accion' => 'incluir',
            'fecha' => '2099-12-31'
        ];

        // Indicamos a PHPUnit que esperamos una excepción
        $this->expectException(Exception::class);
        // Indicamos el mensaje de restricción de fechas futuras
        $this->expectExceptionMessage('La fecha ingresada no puede ser futura.');

        // 2. Act: Ejecutamos el método con la fecha futura
        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 5: Verificar que se lance una excepción si la fecha tiene más de 2 meses de atraso
    public function testProcesarDatosLanzaExcepcionCuandoFechaTieneMasDeDosMesesDeAtraso(): void
    {
        // 1. Arrange: Instanciamos el modelo de pagos
        $modelo = new ModeloPagos();
        // Preparamos datos con una fecha de hace varios años
        $datosInvalidos = [
            'accion' => 'incluir',
            'fecha' => '2020-01-01'
        ];

        // Indicamos a PHPUnit que esperamos una excepción
        $this->expectException(Exception::class);
        // Indicamos el mensaje de restricción de fechas con más de 2 meses de antigüedad
        $this->expectExceptionMessage('La fecha ingresada no puede tener más de 2 meses de atraso.');

        // 2. Act: Ejecutamos la validación de fechas
        $modelo->ProcesarDatos($datosInvalidos);
    }

    // Caso 6: Verificar que Incluir retorne error si no se selecciona ninguna cuenta por cobrar
    public function testRegistrarPagoRetornaErrorCuandoNoSeSeleccionaNingunaCuenta(): void
    {
        // 1. Arrange: Instanciamos el modelo de pagos
        $modelo = new ModeloPagos();
        // Preparamos la acción de inclusión con un arreglo de cuentas vacío
        $datos = [
            'accion' => 'incluir',
            'cuenta' => []
        ];

        // 2. Act: Procesamos los datos de pago
        $resultado = $modelo->ProcesarDatos($datos);

        // 3. Assert: Validamos que la acción retornada sea de error
        $this->assertEquals('error', $resultado['accion']);
        // Validamos que el código de error retornado corresponda a EMPTY_SELECTION
        $this->assertEquals(EMPTY_SELECTION, $resultado['codigo']);
    }

    // Caso 7: Verificar que Incluir retorne error si el monto proporcionado es menor o igual a cero
    public function testRegistrarPagoRetornaErrorCuandoMontoEsMenorOIgualACero(): void
    {
        // 1. Arrange: Instanciamos el modelo de pagos
        $modelo = new ModeloPagos();
        // Preparamos datos válidos pero con monto en cero
        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'monto' => '0',
            'tasa' => '1',
            'fecha' => date('Y-m-d')
        ];

        // 2. Act: Ejecutamos el registro del pago
        $resultado = $modelo->ProcesarDatos($datos);

        // 3. Assert: Validamos que retorne acción de error
        $this->assertEquals('error', $resultado['accion']);
        // Validamos el mensaje del código de error
        $this->assertEquals('El monto del pago debe ser mayor a 0.', $resultado['codigo']);
    }

    // Caso 8: Verificar que Incluir retorne error si la tasa de cambio proporcionada es menor o igual a cero
    public function testRegistrarPagoRetornaErrorCuandoTasaEsMenorOIgualACero(): void
    {
        // 1. Arrange: Instanciamos el modelo de pagos
        $modelo = new ModeloPagos();
        // Preparamos datos con un monto positivo pero tasa en cero
        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'monto' => '100.00',
            'tasa' => '0',
            'fecha' => date('Y-m-d')
        ];

        // 2. Act: Ejecutamos el procesamiento del pago
        $resultado = $modelo->ProcesarDatos($datos);

        // 3. Assert: Validamos que retorne error
        $this->assertEquals('error', $resultado['accion']);
        // Validamos que el mensaje indique que la tasa debe ser mayor a cero
        $this->assertEquals('La tasa de cambio debe ser mayor a 0.', $resultado['codigo']);
    }

    // Caso 9: Verificar que se retorne error cuando el método de pago no existe en la BD (usando Fake y Mock)
    public function testRegistrarPagoRetornaErrorCuandoMetodoDePagoNoExiste(): void
    {
        // 1. Arrange: Simulamos la consulta de base de datos con un Mock de PDOStatement
        $stmtMock = $this->createMock(PDOStatement::class);
        // Indicamos que fetchColumn devolverá 0 (lo que indica que el registro no existe)
        $stmtMock->method('fetchColumn')->willReturn(0);
        // Indicamos que execute devolverá true
        $stmtMock->method('execute')->willReturn(true);

        // Simulamos la conexión con un Mock de PDO
        $pdoMock = $this->createMock(PDO::class);
        // Configuramos prepare para retornar nuestra sentencia simulada
        $pdoMock->method('prepare')->willReturn($stmtMock);
        // Configuramos la apertura de transacción como exitosa
        $pdoMock->method('beginTransaction')->willReturn(true);
        // Configuramos la reversión de transacción como exitosa
        $pdoMock->method('rollBack')->willReturn(true);
        // Indicamos que la conexión se encuentra dentro de una transacción
        $pdoMock->method('inTransaction')->willReturn(true);

        // Inyectamos el Mock en la propiedad singleton de Conexion mediante reflexión
        $reflector = new ReflectionClass(Conexion::class);
        // Obtenemos la propiedad instancia
        $propiedad = $reflector->getProperty('instancia');
        // Asignamos el objeto simulado a la propiedad
        $propiedad->setValue(null, $pdoMock);

        // Creamos una falsificación (Fake) mediante una clase anónima que hereda de ModeloPagos
        $modeloFake = new class extends ModeloPagos {
            // Propiedad estática para almacenar la conexión simulada
            public static $pdoTemporal;

            // Sobrescribimos el método estático conex para devolver el Mock
            public static function conex() {
                return self::$pdoTemporal;
            }

            // Sobrescribimos getConexion para que devuelva el Mock en cualquier llamada interna
            public function getConexion(string $db = 'general') {
                return self::$pdoTemporal;
            }
        };

        // Asignamos el Mock de PDO a la propiedad estática de la clase anónima
        $modeloFake::$pdoTemporal = $pdoMock;

        // Preparamos los datos del pago con un id de método que no existirá
        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'metodo' => '99',
            'moneda' => '1',
            'monto' => '100.00',
            'tasa' => '1',
            'fecha' => date('Y-m-d'),
            'referencia' => '123456'
        ];

        // 2. Act: Ejecutamos el registro de pago en el modelo Fake
        $resultado = $modeloFake->ProcesarDatos($datos);

        // 3. Assert: Verificamos que la acción sea de error
        $this->assertEquals('error', $resultado['accion']);
        // Verificamos que el código de error retornado sea INVALID_ID
        $this->assertEquals(INVALID_ID, $resultado['codigo']);
    }

    // Caso 10: Verificar que se retorne error cuando la moneda seleccionada no existe en la BD
    public function testRegistrarPagoRetornaErrorCuandoMonedaNoExiste(): void
    {
        // 1. Arrange: Creamos el Mock de la sentencia preparada
        $stmtMock = $this->createMock(PDOStatement::class);
        // El primer llamado a fetchColumn (para id_metodo) retorna 1 (existe), el segundo (para id_moneda) retorna 0 (no existe)
        $stmtMock->method('fetchColumn')->willReturnOnConsecutiveCalls(1, 0);
        // El método execute retorna true
        $stmtMock->method('execute')->willReturn(true);

        // Creamos el Mock de la conexión PDO
        $pdoMock = $this->createMock(PDO::class);
        // Asignamos el mock de la sentencia al método prepare
        $pdoMock->method('prepare')->willReturn($stmtMock);
        // Permitimos iniciar transacción
        $pdoMock->method('beginTransaction')->willReturn(true);
        // Permitimos revertir transacción
        $pdoMock->method('rollBack')->willReturn(true);
        // Indicamos que la transacción está activa
        $pdoMock->method('inTransaction')->willReturn(true);

        // Inyectamos el Mock en la conexión global usando reflexión
        $reflector = new ReflectionClass(Conexion::class);
        // Accedemos a la propiedad estática privada instancia
        $propiedad = $reflector->getProperty('instancia');
        // Sobrescribimos el singleton con el mock
        $propiedad->setValue(null, $pdoMock);

        // Creamos el Fake de ModeloPagos
        $modeloFake = new class extends ModeloPagos {
            // Propiedad para guardar el mock
            public static $pdoTemporal;

            // Método que retorna el mock de PDO
            public static function conex() {
                return self::$pdoTemporal;
            }

            // Sobrescritura de getConexion para usar el mock
            public function getConexion(string $db = 'general') {
                return self::$pdoTemporal;
            }
        };

        // Asignamos la conexión simulada al Fake
        $modeloFake::$pdoTemporal = $pdoMock;

        // Datos con método válido y moneda que no existe
        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'metodo' => '1',
            'moneda' => '99',
            'monto' => '100.00',
            'tasa' => '1',
            'fecha' => date('Y-m-d'),
            'referencia' => '123456'
        ];

        // 2. Act: Ejecutamos el registro del pago
        $resultado = $modeloFake->ProcesarDatos($datos);

        // 3. Assert: Verificamos que se detecte el error
        $this->assertEquals('error', $resultado['accion']);
        // Verificamos que el código de error sea INVALID_ID . '0' (específico de moneda no válida)
        $this->assertEquals(INVALID_ID . '0', $resultado['codigo']);
    }

    // Caso 11: Verificar que se retorne error si las cuentas seleccionadas no poseen deuda pendiente
    public function testRegistrarPagoRetornaErrorCuandoCuentasNoPoseenDeudaPendiente(): void
    {
        // 1. Arrange: Creamos el Mock de PDOStatement
        $stmtMock = $this->createMock(PDOStatement::class);
        // El método execute retorna true siempre
        $stmtMock->method('execute')->willReturn(true);
        // El método fetchColumn retorna 1 para confirmar que método y moneda existen
        $stmtMock->method('fetchColumn')->willReturn(1);
        // fetchAll retorna datos válidos para moneda y cuenta
        $stmtMock->method('fetchAll')->willReturn([
            ['codigo_moneda' => 1, 'nombre' => 'Dolar', 'simbolo' => '$', 'monto_total' => '100.00']
        ]);
        // fetch retorna que la cuenta ya tiene abonado el total de 100.00 (monto_pendiente = 0)
        $stmtMock->method('fetch')->willReturn([
            'total_abonado' => '100.00'
        ]);

        // Creamos el Mock de PDO
        $pdoMock = $this->createMock(PDO::class);
        // Asignamos la sentencia al preparar
        $pdoMock->method('prepare')->willReturn($stmtMock);
        // Permitimos iniciar transacción
        $pdoMock->method('beginTransaction')->willReturn(true);
        // Permitimos revertir transacción
        $pdoMock->method('rollBack')->willReturn(true);
        // Indicamos que se encuentra en transacción
        $pdoMock->method('inTransaction')->willReturn(true);
        // Simulamos el ID insertado
        $pdoMock->method('lastInsertId')->willReturn('10');

        // Inyectamos el mock en la clase abstracta Conexion
        $reflector = new ReflectionClass(Conexion::class);
        // Obtenemos la propiedad estática privada
        $propiedad = $reflector->getProperty('instancia');
        // Asignamos la conexión simulada
        $propiedad->setValue(null, $pdoMock);

        // Creamos el Fake de ModeloPagos
        $modeloFake = new class extends ModeloPagos {
            // Variable estática para el mock
            public static $pdoTemporal;

            // Retorna la conexión simulada
            public static function conex() {
                return self::$pdoTemporal;
            }

            // Retorna la conexión simulada en getConexion
            public function getConexion(string $db = 'general') {
                return self::$pdoTemporal;
            }
        };

        // Asignamos el mock al modelo Fake
        $modeloFake::$pdoTemporal = $pdoMock;

        // Enviamos datos para procesar el pago sobre una cuenta saldada
        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'metodo' => '1',
            'moneda' => '1',
            'monto' => '50.00',
            'tasa' => '1',
            'fecha' => date('Y-m-d'),
            'referencia' => '123456'
        ];

        // 2. Act: Ejecutamos el registro de pago
        $resultado = $modeloFake->ProcesarDatos($datos);

        // 3. Assert: Verificamos que retorne error
        $this->assertEquals('error', $resultado['accion']);
        // Verificamos el mensaje de negocio de cuenta sin deuda pendiente
        $this->assertEquals('Las cuentas seleccionadas no poseen deuda pendiente.', $resultado['codigo']);
    }

    // Caso 12: Verificar el registro exitoso de un pago cubriendo el monto exacto de la deuda
    public function testRegistrarPagoExitosoConMontoExacto(): void
    {
        // 1. Arrange: Configuramos el Mock de PDOStatement
        $stmtMock = $this->createMock(PDOStatement::class);
        // Configuramos execute para devolver verdadero
        $stmtMock->method('execute')->willReturn(true);
        // fetchColumn retorna 1 para validar la existencia del método y la moneda
        $stmtMock->method('fetchColumn')->willReturn(1);
        // fetchAll retorna los datos de moneda y los datos de la cuenta por cobrar (total 100.00)
        $stmtMock->method('fetchAll')->willReturn([
            ['codigo_moneda' => 1, 'nombre' => 'Dolar', 'simbolo' => '$', 'monto_total' => '100.00']
        ]);
        // fetch retorna que los abonos previos son 0.00 (deuda pendiente completa de 100.00)
        $stmtMock->method('fetch')->willReturn([
            'total_abonado' => '0.00'
        ]);

        // Configuramos el Mock de PDO
        $pdoMock = $this->createMock(PDO::class);
        // Asignamos la sentencia al preparar consultas
        $pdoMock->method('prepare')->willReturn($stmtMock);
        // Permitimos iniciar la transacción
        $pdoMock->method('beginTransaction')->willReturn(true);
        // Permitimos realizar el commit exitoso de la transacción
        $pdoMock->method('commit')->willReturn(true);
        // Indicamos que el ID generado para el nuevo pago es 25
        $pdoMock->method('lastInsertId')->willReturn('25');

        // Inyectamos el mock en Conexion mediante reflexión
        $reflector = new ReflectionClass(Conexion::class);
        // Obtenemos la propiedad privada
        $propiedad = $reflector->getProperty('instancia');
        // Sobrescribimos con el mock
        $propiedad->setValue(null, $pdoMock);

        // Creamos la clase falsa Fake para ModeloPagos
        $modeloFake = new class extends ModeloPagos {
            // Propiedad estática para el mock
            public static $pdoTemporal;

            // Retorna la conexión temporal simulada
            public static function conex() {
                return self::$pdoTemporal;
            }

            // Retorna la conexión temporal en llamadas internas
            public function getConexion(string $db = 'general') {
                return self::$pdoTemporal;
            }
        };

        // Asignamos el mock al Fake
        $modeloFake::$pdoTemporal = $pdoMock;

        // Preparamos los datos completos para registrar el pago por el monto exacto de 100.00
        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'metodo' => '1',
            'moneda' => '1',
            'monto' => '100.00',
            'tasa' => '1',
            'fecha' => date('Y-m-d'),
            'referencia' => '123456'
        ];

        // 2. Act: Ejecutamos el registro del pago
        $resultado = $modeloFake->ProcesarDatos($datos);

        // 3. Assert: Verificamos que la acción resultante sea exito
        $this->assertEquals('exito', $resultado['accion']);
        // Verificamos que el ID del pago coincida con el generado en la simulación
        $this->assertEquals('25', $resultado['id_pago']);
        // Verificamos que no haya vuelto restante (monto exacto = 0)
        $this->assertEquals(0, $resultado['vuelto']);
    }

    // Caso 13: Verificar el registro exitoso de un pago con cálculo de vuelto o excedente
    public function testRegistrarPagoExitosoGenerandoVuelto(): void
    {
        // 1. Arrange: Configuramos el Mock de sentencia
        $stmtMock = $this->createMock(PDOStatement::class);
        // execute retorna true
        $stmtMock->method('execute')->willReturn(true);
        // fetchColumn retorna 1 para comprobaciones de método y moneda
        $stmtMock->method('fetchColumn')->willReturn(1);
        // fetchAll devuelve deuda de 60.00 para la cuenta
        $stmtMock->method('fetchAll')->willReturn([
            ['codigo_moneda' => 1, 'nombre' => 'Dolar', 'simbolo' => '$', 'monto_total' => '60.00']
        ]);
        // fetch devuelve que no hay abonos previos
        $stmtMock->method('fetch')->willReturn([
            'total_abonado' => '0.00'
        ]);

        // Configuramos el Mock de PDO
        $pdoMock = $this->createMock(PDO::class);
        // prepare retorna la sentencia simulada
        $pdoMock->method('prepare')->willReturn($stmtMock);
        // beginTransaction retorna true
        $pdoMock->method('beginTransaction')->willReturn(true);
        // commit retorna true
        $pdoMock->method('commit')->willReturn(true);
        // lastInsertId retorna el ID 30
        $pdoMock->method('lastInsertId')->willReturn('30');

        // Inyectamos el mock en Conexion
        $reflector = new ReflectionClass(Conexion::class);
        // Obtenemos la propiedad instancia
        $propiedad = $reflector->getProperty('instancia');
        // Asignamos el mock
        $propiedad->setValue(null, $pdoMock);

        // Creamos el Fake de ModeloPagos
        $modeloFake = new class extends ModeloPagos {
            // Propiedad para el mock
            public static $pdoTemporal;

            // Retorna el mock en conex
            public static function conex() {
                return self::$pdoTemporal;
            }

            // Retorna el mock en getConexion
            public function getConexion(string $db = 'general') {
                return self::$pdoTemporal;
            }
        };

        // Asignamos el mock de PDO
        $modeloFake::$pdoTemporal = $pdoMock;

        // Pagamos 100.00 para una deuda de 60.00 (el vuelto esperado es 40.00)
        $datos = [
            'accion' => 'incluir',
            'cuenta' => ['1'],
            'metodo' => '1',
            'moneda' => '1',
            'monto' => '100.00',
            'tasa' => '1',
            'fecha' => date('Y-m-d'),
            'referencia' => '123456'
        ];

        // 2. Act: Ejecutamos el registro del pago
        $resultado = $modeloFake->ProcesarDatos($datos);

        // 3. Assert: Verificamos que sea exitoso
        $this->assertEquals('exito', $resultado['accion']);
        // Verificamos el ID de pago
        $this->assertEquals('30', $resultado['id_pago']);
        // Verificamos que el excedente calculado como vuelto sea exactamente 40.00
        $this->assertEquals(40.00, $resultado['vuelto']);
    }
}
