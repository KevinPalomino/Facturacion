<?php
include "db.php";

if (isset($_POST['query'])) {
    $query = $conn->real_escape_string($_POST['query']);
    $sql = "SELECT id_producto, nombre, stock FROM producto 
            WHERE nombre LIKE '%$query%' OR id_producto LIKE '%$query%' 
            LIMIT 10";

    $resultado = $conn->query($sql);

    while ($row = $resultado->fetch_assoc()) {
        $stock = (int)$row['stock'];
        $alerta = '';

        if ($stock == 0) {
            $alerta = '⚠ SIN STOCK';
        } elseif ($stock <= max(1, round($stock * 0.2))) {
            $alerta = '⚠ STOCK BAJO';
        }

        echo "<div class='producto-sugerido' 
                    data-id='{$row['id_producto']}' 
                    data-nombre='{$row['nombre']}'
                    style='padding:5px; cursor:pointer; border-bottom:1px solid #eee'>
                <strong>{$row['id_producto']}</strong> - {$row['nombre']} (Stock: {$stock}) {$alerta}
              </div>";
    }
}
