<?php
include("db.php");

if (isset($_POST['query'])) {
    $query = mysqli_real_escape_string($conn, $_POST['query']);
    $sql = "SELECT id_cliente, nombre, apellido FROM cliente WHERE id_cliente LIKE '%$query%' LIMIT 5";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        while ($cliente = mysqli_fetch_assoc($result)) {
            echo '<div class="cliente-sugerido" data-id="' . $cliente['id_cliente'] . '" style="padding:5px; cursor:pointer;">' .
                $cliente['id_cliente'] . ' - ' . $cliente['nombre'] . ' ' . $cliente['apellido'] .
                '</div>';
        }
    } else {
    echo '<div style="padding:5px;">Cliente no encontrado. <a href="../cajero/registrar_cliente.php">Registrar</a></div>';
    }
}
