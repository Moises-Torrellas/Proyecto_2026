import requests
import os
from dotenv import load_dotenv

# Carga la clave secreta desde el archivo .env
load_dotenv()
api_key = os.getenv("GEMINI_API_KEY")

# Verificamos que la clave se haya cargado correctamente
if not api_key:
    print("Error: No se encontró la GEMINI_API_KEY en el archivo .env")
    exit()

# Le preguntamos a Google qué modelos existen para tu cuenta
url = f"https://generativelanguage.googleapis.com/v1beta/models?key={api_key}"

print("Consultando a Google...")
respuesta = requests.get(url)

if respuesta.status_code == 200:
    print("\n¡Éxito! Estos son los nombres exactos que puedes usar:\n")
    modelos = respuesta.json().get('models', [])
    for modelo in modelos:
        # Solo imprimimos los que sirven para generar texto
        if 'generateContent' in modelo.get('supportedGenerationMethods', []):
            print(modelo['name'])
else:
    print("\nHubo un error con la API Key:")
    print(respuesta.text)