<?php
// cierre_caja.php — Resumen de ventas del día con paginación y buscador por ID de cliente
session_start();
include "../includes/db.php";
include "../includes/auth.php";
include "../includes/header.php";
verificarRol("CAJERO");

// Parámetros de paginación
$limite = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina - 1) * $limite;

// Búsqueda (opcional por ID de cliente)
$busqueda = isset($_GET['buscar']) ? $conn->real_escape_string($_GET['buscar']) : '';
$where = "WHERE DATE(f.fecha) = CURDATE()";

if (!empty($busqueda)) {
    $where .= " AND c.id_cliente LIKE '%$busqueda%'";
}

// Total de registros
$total_query = $conn->query("SELECT COUNT(*) AS total FROM factura f JOIN cliente c ON f.id_cliente = c.id_cliente $where");
$total_resultado = $total_query->fetch_assoc()['total'];
$total_paginas = ceil($total_resultado / $limite);

// Consulta de facturas con límite
$query = $conn->query("SELECT f.num_factura, f.fecha, c.nombre, c.apellido, m.nombre AS metodo_pago, (SELECT SUM(d.cantidad * d.precio) FROM detalle d WHERE d.id_factura = f.num_factura) AS total FROM factura f JOIN cliente c ON f.id_cliente = c.id_cliente JOIN modo_pago m ON f.num_pago = m.num_pago $where ORDER BY f.num_factura DESC LIMIT $inicio, $limite");
?>

<div class="content">
    <h2>Cierre de Caja - <?php echo date('Y-m-d'); ?></h2>

    <form method="GET" style="margin-bottom: 20px;">
        <input type="text" name="buscar" placeholder="Buscar por ID de cliente" value="<?= htmlspecialchars($busqueda) ?>">
        <button type="submit">Buscar</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>N° Factura</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Método de Pago</th>
                <th>Total</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $query->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['num_factura'] ?></td>
                    <td><?= $row['nombre'] . ' ' . $row['apellido'] ?></td>
                    <td><?= $row['fecha'] ?></td>
                    <td><?= $row['metodo_pago'] ?></td>
                    <td>$<?= number_format($row['total'] ?? 0, 2) ?></td>
                    <td>
                        <a href="imprimir_factura.php?id=<?= $row['num_factura'] ?>" target="_blank" title="Imprimir">
                            🖨️ Ver / Imprimir
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <?php if ($total_paginas > 1): ?>
        <div style="margin-top:20px; text-align:center;">
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="?pagina=<?= $i ?>&buscar=<?= urlencode($busqueda) ?>" style="margin:0 5px; padding:5px 10px; background-color:<?= $i == $pagina ? '#3498db' : '#ecf0f1' ?>; color:<?= $i == $pagina ? '#fff' : '#333' ?>; border-radius:4px; text-decoration:none;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>