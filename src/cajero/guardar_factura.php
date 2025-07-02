<?php
include "../includes/db.php";
include "../includes/auth.php";
include "../includes/header.php";
verificarRol("CAJERO");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente = $_POST["id_cliente"];
    $num_pago = $_POST["num_pago"];
    $productos = json_decode($_POST["items_json"], true); // ✅ ahora sí obtienes un array


    // Validar stock
    foreach ($productos as $datos) {
        $id_producto = (int)$datos["id"];
        $cantidad = (int)$datos["cantidad"];


        if ($cantidad > 0) {
            // Obtener stock actual
            $stmt = $conn->prepare("SELECT stock FROM producto WHERE id_producto = ?");
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            $stmt->bind_result($stock_actual);
            $stmt->fetch();
            $stmt->close();

            if ($cantidad > $stock_actual) {
                echo "❌ Error: La cantidad solicitada del producto con ID $id_producto excede el stock disponible ($stock_actual).";
                exit;
            }
        }
    }

    // Insertar factura
    $stmt = $conn->prepare("INSERT INTO factura (id_cliente, fecha, num_pago) VALUES (?, NOW(), ?)");
    $stmt->bind_param("ii", $id_cliente, $num_pago);
    $stmt->execute();
    $num_factura = $stmt->insert_id;
    $stmt->close();

    // Insertar detalles
    foreach ($productos as $datos) {
        $id_producto = (int)$datos["id"];
        $cantidad = (int)$datos["cantidad"];

        $precio = (float)$datos["precio"];

        if ($cantidad > 0) {
            // Insertar en detalle
            $stmt = $conn->prepare("INSERT INTO detalle (id_factura, id_producto, cantidad, precio) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $num_factura, $id_producto, $cantidad, $precio);
            $stmt->execute();
            $stmt->close();

            // Actualizar stock
            $stmt = $conn->prepare("UPDATE producto SET stock = stock - ? WHERE id_producto = ?");
            $stmt->bind_param("ii", $cantidad, $id_producto);
            $stmt->execute();
            $stmt->close();
        }
    }

    echo "✅ Factura registrada correctamente. <a href='nueva_factura.php'>Crear otra</a>";
} else {
    echo "Acceso denegado.";
}
