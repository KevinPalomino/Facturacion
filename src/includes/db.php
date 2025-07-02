<?php
$host = "localhost";
$user = "root";
$pass = "toor";
$db = "sistema_facturacion";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
?>