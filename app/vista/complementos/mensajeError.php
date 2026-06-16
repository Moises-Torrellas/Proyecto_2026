<?php if (!empty($error_bd)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Fallo de Conexión',
                    text: '<?= $error_bd ?>', // Aquí se imprimirá tu constante DB_CONNECTION
                    confirmButtonColor: '#d30000',
                    confirmButtonText: 'Entendido',
                    customClass: {
                        popup: "mi-popup",
                        title: "mi-titulo",
                        content: "mi-contenido"
                    }
                });
            });
        </script>
    <?php endif; ?>