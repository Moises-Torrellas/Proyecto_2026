from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import requests
import os
from dotenv import load_dotenv

# Carga las variables del archivo .env al entorno de Python
load_dotenv()

app = FastAPI()

class PromptRequest(BaseModel):
    texto: str

@app.post("/api/generar-respuesta")
def generar_respuesta_ia(request: PromptRequest):
    # 1. Configuración de API Key (leyendo desde el .env) y URL de Gemini
    api_key = os.getenv("GEMINI_API_KEY")
    modelo = "gemini-3.1-flash-lite"  # Asegúrate de usar el modelo correcto
    
    # 3. LA URL CORREGIDA
    url = f"https://generativelanguage.googleapis.com/v1beta/models/{modelo}:generateContent?key={api_key}"
    
    # Gemini solo necesita este encabezado
    headers = {
        "Content-Type": "application/json"
    }
    
    try:
        # Formato exacto que pide Gemini API
        payload = {
            "contents": [
                {
                    "parts": [
                        {"text": request.texto}
                    ]
                }
            ]
        }
        
        # Hacemos la llamada a Gemini
        response = requests.post(url, headers=headers, json=payload)
        
        # Validar si Gemini rechazó la petición (Error de API Key, cuota, etc.)
        if not response.ok:
            # Forzamos un retorno HTTP 200 pero con status "error" para que PHP lo muestre en pantalla
            return {
                "status": "error", 
                "mensaje": f"Gemini devolvió un error ({response.status_code}): {response.text}"
            }
            
        resultado_json = response.json()
        
        # Validar estructura de respuesta
        if 'candidates' not in resultado_json or len(resultado_json['candidates']) == 0:
            return {"status": "error", "mensaje": "La IA de Gemini no generó ninguna respuesta válida."}
            
        respuesta_generada = resultado_json['candidates'][0]['content']['parts'][0]['text']
        
        return {"status": "success", "data": respuesta_generada}
        
    except Exception as e:
        # En vez de un error 500 que PHP no sabe leer bien, devolvemos un JSON de error estructurado
        return {"status": "error", "mensaje": f"Fallo interno en el microservicio de Python: {str(e)}"}