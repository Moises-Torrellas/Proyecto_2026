import requests

# Pon tu API Key aquí
api_key = "AIzaSyConTzKH0aFlRwdBEawrdkRriNuFdPXlzU"

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