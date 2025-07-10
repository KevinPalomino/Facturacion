<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function verificarRol($rolPermitido)
{
    $ruta_login = "/EjerciciosPracticos/PruebaFacturacion/src/index.php";

    if (!isset($_SESSION['correo']) || !isset($_SESSION['rol'])) {
        header("Location: $ruta_login");
        exit();
    }

    if (strtolower($_SESSION['rol']) !== strtolower($rolPermitido)) {
        header("Location: $ruta_login");
        exit();
    }
}
