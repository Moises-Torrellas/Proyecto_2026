<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: sans-serif;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #1a1a1a;
            color: #46d1f1;
            padding: 10px;
            text-align: left;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
            font-size: 12px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #46d1f1;
            padding-bottom: 10px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: right;
            font-size: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Reporte de Usuarios</h1>
        <p>Generado el: <?php echo date('d/m/Y h:i A'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Cédula</th>
                <th>Nombre</th>
                <th>Apellido</th>
                <th>Correo</th>
                <th>Rol</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 0; ?>
            <?php foreach ($datos as $u): ?>
                <?php $i++; ?>
                <tr>
                    <td><?php echo $i ?></td>
                    <td><?php echo $u['cedulaUsuario']; ?></td>
                    <td><?php echo $u['nombreUsuario']; ?></td>
                    <td><?php echo $u['apellidoUsuario']; ?></td>
                    <td><?php echo $u['correo']; ?></td>
                    <td><?php echo $u['nombre_rol']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        Página 1
    </div>
</body>

</html>