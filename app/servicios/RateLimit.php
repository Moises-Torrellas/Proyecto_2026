<?php
namespace App\servicios;

class RateLimit
{
    private string $almacenamiento;
    private int $intentosMaximos;
    private int $segundos;

    public function __construct(int $intentosMaximos = 20, int $segundos = 60)
    {
        $this->almacenamiento = __DIR__ . '/../../storage/limites_frecuencia/';
        $this->intentosMaximos = $intentosMaximos;
        $this->segundos = $segundos;

        if (!is_dir($this->almacenamiento)) {
            mkdir($this->almacenamiento, 0700, true);
        }
    }

    public function estaLimitado(string $ip): bool
    {
        $archivo = $this->obtenerRutaArchivo($ip);
        $this->limpiar($archivo);

        $intentos = $this->obtenerIntentos($archivo);
        return count($intentos) >= $this->intentosMaximos;
    }

    public function registrarIntento(string $ip): void
    {
        $archivo = $this->obtenerRutaArchivo($ip);
        $intentos = $this->obtenerIntentos($archivo);
        $intentos[] = time();
        file_put_contents($archivo, json_encode($intentos), LOCK_EX);
    }

    public function obtenerSegundosRestantes(string $ip): int
    {
        $archivo = $this->obtenerRutaArchivo($ip);
        $intentos = $this->obtenerIntentos($archivo);
        if (empty($intentos)) return 0;

        $masAntiguo = min($intentos);
        $restantes = ($masAntiguo + $this->segundos) - time();
        return max(0, $restantes);
    }

    private function obtenerRutaArchivo(string $ip): string
    {
        return $this->almacenamiento . md5($ip) . '.json';
    }

    private function obtenerIntentos(string $archivo): array
    {
        if (!file_exists($archivo)) return [];

        $datos = json_decode(file_get_contents($archivo), true);
        if (!is_array($datos)) return [];

        $ahora = time();
        // Solo conservar intentos dentro de la ventana de tiempo
        return array_values(array_filter($datos, fn($tiempo) => ($ahora - $tiempo) < $this->segundos));
    }

    private function limpiar(string $archivo): void
    {
        $intentos = $this->obtenerIntentos($archivo);
        file_put_contents($archivo, json_encode($intentos), LOCK_EX);
    }
}