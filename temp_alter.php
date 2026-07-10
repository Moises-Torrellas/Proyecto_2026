<?php
require 'config.php';
require 'app/modelo/Conexion.php';
$db = App\modelo\Conexion::getInstance();
$db->exec('ALTER TABLE vueltos ADD COLUMN monto_base_vuelto DECIMAL(15,2) NULL, ADD COLUMN tasa_vuelto DECIMAL(10,4) NULL;');
echo 'OK';
