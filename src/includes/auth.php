<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
function verificarRol($rolPermitido)
{
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== $rolPermitido) {
        header("Location: ../index.php");
        exit();
    }
}
