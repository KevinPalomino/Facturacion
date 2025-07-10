<?php
session_start();

if (!isset($_SESSION['correo'])) {
    header("Location: http://localhost/EjerciciosPracticos/PruebaFacturacion/src/index.php");
    exit();
}

$rol = strtolower(trim($_SESSION['rol']));

switch ($rol) {
    case 'global':
        header("Location: global/usuarios.php");
        exit();
    case 'administrador':
        header("Location: admin/informe_ventas.php");
        exit();
    case 'cajero':
        header("Location: cajero/nueva_factura.php");
        exit();
    default:
        echo "⚠️ Rol desconocido: " . htmlspecialchars($_SESSION['rol']);
        exit();
}
