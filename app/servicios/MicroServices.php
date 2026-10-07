<?php

namespace App\servicios;

use Exception;

class Microservices {
    
    // URL de tu microservicio en Python (FastAPI)
    private $urlIA = "http://127.0.0.1:8000/api/generar-respuesta";

    public function consultarGemini($textoPrompt) {
        $data = [
            "texto" => $textoPrompt
        ];

        $ch = curl_init($this->urlIA);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); 

        $respuesta = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);

        if ($error) {
            throw new Exception("Error al conectar con el microservicio: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("El microservicio de IA respondió con código: " . $httpCode);
        }

        $resultadoDecodificado = json_decode($respuesta, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error al decodificar la respuesta JSON del microservicio.");
        }

        return $resultadoDecodificado;
    }
}