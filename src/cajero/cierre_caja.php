<?php
include "../includes/auth.php";
include "../includes/db.php";
include "../includes/header.php";
verificarRol("CAJERO");

// Mostrar ventas del día
$sql = "
SELECT f.num_factura, f.fecha, c.id_cliente  AS cliente, SUM(d.cantidad * p.precio) AS total
FROM factura f
JOIN cliente c ON f.id_cliente = c.id_cliente
JOIN detalle d ON d.id_factura = f.num_factura
JOIN producto p ON p.id_producto = d.id_producto
WHERE DATE(f.fecha) = CURDATE()
GROUP BY f.num_factura 
";

$result = $conn->query($sql);
$total_dia = 0;
?>

<h2>Cierre de Caja - <?php echo date('Y-m-d'); ?></h2>
<table border="1">
    <tr>
        <th>ID Factura</th>
        <th>Fecha</th>
        <th>Cliente</th>
        <th>Total</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['num_factura'] ?></td>
            <td><?= $row['fecha'] ?></td>
            <td><?= $row['cliente'] ?></td>
            <td>$<?= number_format($row['total'], 2) ?></td>
        </tr>
        <?php $total_dia += $row['total']; ?>
    <?php endwhile; ?>
</table>
<h3>Total del día: $<?= number_format($total_dia, 2) ?></h3>