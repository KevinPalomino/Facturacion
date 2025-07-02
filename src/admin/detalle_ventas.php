<?php
include "../includes/db.php";

$fecha = $_GET['fecha'] ?? '';

if (!$fecha) {
    echo "⚠️ Fecha no proporcionada.";
    exit;
}

$sql = "SELECT 
            f.num_factura,
            CONCAT(c.nombre, ' ', c.apellido) AS cliente,
            p.nombre AS producto,
            d.cantidad,
            d.precio,
            (d.cantidad * d.precio) AS subtotal
        FROM factura f
        JOIN cliente c ON f.id_cliente = c.id_cliente
        JOIN detalle d ON f.num_factura = d.id_factura
        JOIN producto p ON d.id_producto = p.id_producto
        WHERE DATE(f.fecha) = ?
        ORDER BY f.num_factura, cliente, producto";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $fecha);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    echo "⚠️ No hay datos de ventas para esta fecha.";
    exit;
}

// Encabezado tabla
echo "<table border='1' width='100%' cellpadding='5' cellspacing='0'>";
echo "<tr style='background-color:#f2f2f2;'>
        <th>Factura</th>
        <th>Cliente</th>
        <th>Producto</th>
        <th>Cantidad</th>
        <th>Precio Unitario</th>
        <th>Subtotal</th>
      </tr>";

$totalFacturaActual = 0;
$facturaAnterior = null;

while ($row = $resultado->fetch_assoc()) {
    $factura = $row['num_factura'];
    $cliente = htmlspecialchars($row['cliente']);
    $producto = htmlspecialchars($row['producto']);
    $cantidad = $row['cantidad'];
    $precio = number_format($row['precio'], 2);
    $subtotal = number_format($row['subtotal'], 2);

    // Mostrar cliente solo una vez por factura
    $mostrarCliente = ($factura !== $facturaAnterior) ? $cliente : '';
    $mostrarFactura = ($factura !== $facturaAnterior) ? $factura : '';

    echo "<tr>
            <td>$mostrarFactura</td>
            <td>$mostrarCliente</td>
            <td>$producto</td>
            <td align='center'>$cantidad</td>
            <td align='right'>\$ $precio</td>
            <td align='right'>\$ $subtotal</td>
          </tr>";

    $facturaAnterior = $factura;
}

echo "</table>";
