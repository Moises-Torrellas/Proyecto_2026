from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
import requests

app = FastAPI()

class PromptRequest(BaseModel):
    texto: str

@app.post("/api/generar-respuesta")
def generar_respuesta_ia(request: PromptRequest):
    # 1. Configuración de API Key y URL de Gemini
    api_key = "AIzaSyConTzKH0aFlRwdBEawrdkRriNuFdPXlzU"
    modelo = "gemini-3-flash-preview"  # Asegúrate de usar el nombre correcto del modelo que obtuviste con ver_modelos.py
    
    # 3. LA URL CORREGIDA)
    url = f"https://generativelanguage.googleapis.com/v1beta/models/{modelo}:generateContent?key={api_key}"
    
    # Gemini solo necesita este encabezado
    headers = {
        "Content-Type": "application/json"
    }
    
    # 2. El cuerpo de la petición
    data = {
        "contents": [
            {
                "parts": [
                    {"text": request.texto}
                ]
            }
        ]
    }
    
    try:
        # 3. Hacemos la llamada a Gemini
        response = requests.post(url, headers=headers, json=data)
        
        # Lanzará un error si Gemini rechaza la petición
        response.raise_for_status() 
        
        # 4. Extraemos el texto de la respuesta JSON
        resultado_json = response.json()
        respuesta_generada = resultado_json['candidates'][0]['content']['parts'][0]['text']
        
        return {"status": "success", "data": respuesta_generada}
        
    except requests.exceptions.RequestException as e:
        # Si algo falla, le devolvemos el error 500
        raise HTTPException(status_code=500, detail=f"Error de conexión con Gemini: {str(e)}")