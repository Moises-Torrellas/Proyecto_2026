// public/js/preguntasFrecuentes.js

document.addEventListener('DOMContentLoaded', function () {
    const faqQuestions = document.querySelectorAll('.faq_question');
    const searchInput = document.getElementById('busqueda');
    const faqItems = document.querySelectorAll('.faq_item');
    const faqSections = document.querySelectorAll('.faq_section');
    const noResultsMsg = document.querySelector('.faq_no_results');

    // Lógica para el acordeón
    faqQuestions.forEach(question => {
        question.addEventListener('click', () => {
            const answer = question.nextElementSibling;
            const isActive = question.classList.contains('active');

            // Opcional: Cerrar todos los demás acordeones antes de abrir este
            // faqQuestions.forEach(q => {
            //     q.classList.remove('active');
            //     q.nextElementSibling.style.maxHeight = null;
            //     q.nextElementSibling.style.padding = '0 20px';
            // });

            if (isActive) {
                question.classList.remove('active');
                answer.style.maxHeight = null;
                answer.style.padding = '0 20px';
            } else {
                question.classList.add('active');
                answer.style.maxHeight = answer.scrollHeight + "px";
                answer.style.padding = '15px 20px';
            }
        });
    });

    // Lógica para el buscador
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase().trim();
            let totalMatches = 0;

            faqItems.forEach(item => {
                const questionText = item.querySelector('.faq_question').textContent.toLowerCase();
                const answerText = item.querySelector('.faq_answer').textContent.toLowerCase();

                if (questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
                    item.style.display = 'block';
                    totalMatches++;
                } else {
                    item.style.display = 'none';
                    // Si estaba abierto y se oculta, lo cerramos para mantener el orden
                    const btn = item.querySelector('.faq_question');
                    const ans = item.querySelector('.faq_answer');
                    if(btn.classList.contains('active')){
                        btn.classList.remove('active');
                        ans.style.maxHeight = null;
                        ans.style.padding = '0 20px';
                    }
                }
            });

            // Ocultar categorías enteras si no tienen elementos visibles
            faqSections.forEach(section => {
                const visibleItems = section.querySelectorAll('.faq_item[style="display: block;"], .faq_item:not([style*="display: none"])');
                if (visibleItems.length === 0 && searchTerm !== "") {
                    section.style.display = 'none';
                } else {
                    section.style.display = 'block';
                }
            });

            // Mostrar mensaje si no hay resultados
            if (totalMatches === 0 && searchTerm !== "") {
                noResultsMsg.style.display = 'block';
            } else {
                noResultsMsg.style.display = 'none';
            }
        });
    }

    // Lógica para abrir manual de usuario
    const btnManual = document.getElementById('incluir');
    if (btnManual) {
        btnManual.addEventListener('click', function () {
            confirmar('¿Está seguro de que quiere abrir el manual de usuario en otra pestaña?', function (confirmado) {
                if (confirmado) {
                    var datos = new FormData();
                    datos.append('accion', 'obtener_manual');
                    enviaAjax(datos);
                }
            });
        });
    }
});

function enviaAjax(datos) {
    $.ajax({
        async: true,
        url: "",
        type: "POST",
        contentType: false,
        data: datos,
        processData: false,
        cache: false,
        success: function (respuesta) {
            try {
                var lee = JSON.parse(respuesta);
                if (lee.accion === "reporte") {
                    window.open(lee.archivo, '_blank');
                }
            } catch (e) {
                console.error("Error al procesar la respuesta:", e);
            }
        },
        error: function (error) {
            console.error("Error en la petición:", error);
        }
    });
}
