<?php

session_start();
// include "includes/header.php";

if (!isset($_SESSION['correo'])) {
    header("Location: index.php");
    exit();
}

// DEBUG
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

$rol = strtolower(trim($_SESSION['rol']));
echo "ROL NORMALIZADO: $rol<br>";

switch ($rol) {
    case 'global':
        echo "Redirigiendo a global...";
        header("Location: global/usuarios.php");
        exit();
    case 'administrador':
        echo "Redirigiendo a admin...";
        header("Location: admin/informe_ventas.php");
        exit();
    case 'cajero':
        echo "Redirigiendo a cajero...";
        header("Location: cajero/nueva_factura.php");
        exit();
    default:
        echo "Rol desconocido: " . htmlspecialchars($_SESSION['rol']);
        exit();
}
