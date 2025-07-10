<?php
// cierre_caja.php — Resumen de ventas del día con paginación y buscador por ID de cliente
session_start();
include "../includes/auth.php";
verificarRol("CAJERO");
include "../includes/db.php";
include "../includes/header.php";

// Parámetros de paginación
$limite = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina - 1) * $limite;

// Búsqueda por cédula
$busqueda = isset($_GET['buscar']) ? $conn->real_escape_string($_GET['buscar']) : '';
$where = "WHERE DATE(f.fecha) = CURDATE()";
if (!empty($busqueda)) {
    $where .= " AND c.id_cliente LIKE '%$busqueda%'";
}

// Total de registros
$total_query = $conn->query("SELECT COUNT(*) AS total FROM factura f JOIN cliente c ON f.id_cliente = c.id_cliente $where");
$total_resultado = $total_query->fetch_assoc()['total'];
$total_paginas = ceil($total_resultado / $limite);

// Consulta de facturas
$query = $conn->query("
    SELECT f.num_factura, f.fecha, c.nombre, c.apellido, m.nombre AS metodo_pago,
    (SELECT SUM(d.cantidad * d.precio) FROM detalle d WHERE d.id_factura = f.num_factura) AS total
    FROM factura f
    JOIN cliente c ON f.id_cliente = c.id_cliente
    JOIN modo_pago m ON f.num_pago = m.num_pago
    $where
    ORDER BY f.num_factura DESC
    LIMIT $inicio, $limite
");

// Total recaudado del día
$sql_total_hoy = "
    SELECT SUM(d.cantidad * d.precio) AS total_recaudado
    FROM factura f
    JOIN detalle d ON f.num_factura = d.id_factura
    WHERE DATE(f.fecha) = CURDATE()
";
$result_total_hoy = $conn->query($sql_total_hoy);
$fila_total = $result_total_hoy->fetch_assoc();
$total_recaudado_hoy = $fila_total['total_recaudado'] ?? 0;
?>

<div class="content">
    <h2>Cierre de Caja - <?= date('Y-m-d') ?></h2>

    <!-- Buscador con sugerencias y total del día -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; position: relative;">
        <form method="GET" id="form-busqueda">
            <input type="hidden" name="buscar" id="buscar_hidden">
            <input type="text" id="buscar_cliente" autocomplete="off" placeholder="Buscar cliente por cédula" value="<?= htmlspecialchars($busqueda) ?>">
            <div id="sugerencias_cliente" style="position: absolute; background: white; border: 1px solid #ccc; z-index: 100; width: 100%; display: none;"></div>
        </form>

        <div style="background: #f0fff5; border-left: 5px solid #2ecc71; padding: 10px 15px; border-radius: 5px; font-weight: bold;">
            💰 Total Día: $<?= number_format($total_recaudado_hoy, 2) ?>
        </div>
    </div>

    <!-- Tabla de facturas -->
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

    <!-- Navegación -->
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

<!-- Script de autocompletar -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#buscar_cliente').on('keyup', function() {
            var query = $(this).val();
            if (query.length > 0) {
                $.ajax({
                    url: '../includes/buscar_clientes.php',
                    method: 'POST',
                    data: {
                        query: query
                    },
                    success: function(data) {
                        $('#sugerencias_cliente').fadeIn().html(data);
                    }
                });
            } else {
                $('#sugerencias_cliente').fadeOut();
            }
        });

        $(document).on('click', '.cliente-sugerido', function() {
            var id = $(this).data('id');
            $('#buscar_cliente').val(id);
            $('#buscar_hidden').val(id);
            $('#sugerencias_cliente').fadeOut();
            $('#form-busqueda').submit();
        });
    });
</script>