document.addEventListener("DOMContentLoaded", () => {
    const inputPregunta = document.getElementById("input_pregunta_cani");
    const btnEnviar = document.getElementById("btn_enviar_cani");
    const historialChat = document.getElementById("historial_chat");

    // Función para imprimir mensajes en el chat
    const agregarMensaje = (texto, emisor) => {
        const divBurbuja = document.createElement("div");
        // Asignamos una clase diferente si es el usuario o la IA (Cani)
        divBurbuja.classList.add(emisor === "usuario" ? "burbuja_usuario" : "burbuja_ia");
        
        // Convertimos saltos de línea a <br> para que se vea bien el HTML
        const textoFormateado = texto.replace(/\n/g, "<br>");
        divBurbuja.innerHTML = `<p>${textoFormateado}</p>`;
        
        historialChat.appendChild(divBurbuja);
        
        // Auto-scroll hacia abajo para ver el último mensaje
        historialChat.scrollTop = historialChat.scrollHeight;
    };

    const procesarPregunta = async () => {
        const pregunta = inputPregunta.value.trim();
        if (pregunta === "") return; // No enviar si está vacío

        // 1. Mostrar la pregunta del usuario en el chat
        agregarMensaje(pregunta, "usuario");
        inputPregunta.value = ""; // Limpiar el input

        // 2. Mostrar indicador de "Cani está escribiendo..."
        const idCargando = "cargando_" + Date.now();
        const divCargando = document.createElement("div");
        divCargando.id = idCargando;
        divCargando.classList.add("burbuja_ia");
        divCargando.innerHTML = `<p><i>Cani está pensando...</i></p>`;
        historialChat.appendChild(divCargando);
        historialChat.scrollTop = historialChat.scrollHeight;

        try {
            // 3. Preparar los datos para enviarlos a PHP
            const formData = new FormData();
            formData.append('accion', 'generar');
            formData.append('pregunta', pregunta);

            // IMPORTANTE: Asegúrate de apuntar a la ruta correcta de tu enrutador
            const respuesta = await fetch('?pagina=ia', { 
                method: 'POST',
                body: formData,
                headers: {
                    // Si tu sistema usa CSRF tokens en JS, agrégalo aquí
                    // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    
                    // Este header es crucial para que tu función PHP comprobarAjax() devuelva TRUE
                    'X-Requested-With': 'XMLHttpRequest' 
                }
            });

            const datos = await respuesta.json();

            // 4. Remover el mensaje de "pensando..."
            document.getElementById(idCargando).remove();

            // 5. Mostrar la respuesta de Gemini o el error
            if (datos.status === 'success') {
                agregarMensaje(datos.data, "ia");
            } else {
                agregarMensaje("Uy, ocurrió un error: " + datos.mensaje, "ia");
            }

        } catch (error) {
            document.getElementById(idCargando).remove();
            agregarMensaje("Error de conexión con el servidor.", "ia");
            console.error(error);
        }
    };

    // Evento al hacer clic en enviar
    btnEnviar.addEventListener("click", procesarPregunta);

    // Evento al presionar la tecla Enter
    inputPregunta.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            procesarPregunta();
        }
    });
});