<?php
session_start();
include "../includes/auth.php";
verificarRol("ADMINISTRADOR");
include "../includes/header.php";
include "../includes/db.php";



// Agrupar ventas por fecha (día)
$sql = "SELECT DATE(f.fecha) AS fecha, SUM(d.cantidad * d.precio) AS total_dia
        FROM factura f
        JOIN detalle d ON f.num_factura = d.id_factura
        GROUP BY DATE(f.fecha)
        ORDER BY fecha DESC";

$result = $conn->query($sql);
?>


<h2>Informe de Ventas</h2>

<table border="1" cellpadding="5">
    <tr>
        <th>Fecha</th>
        <th>Total del Día</th>
        <th>Detalle</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['fecha'] ?></td>
            <td>$<?= number_format($row['total_dia'], 2) ?></td>
            <td>
                <!-- Botón mostrar detalle -->
                <button onclick="mostrarDetalle('<?= $row['fecha'] ?>')">📋</button>

                <!-- Descargar PDF -->
                <a href="descargar_pdf.php?fecha=<?= $row['fecha'] ?>" target="_blank" title="Descargar PDF">📄</a>
            </td>
        </tr>

        <!-- Fila oculta para detalle -->
        <tr id="detalle-<?= $row['fecha'] ?>" style="display:none;">
            <td colspan="3">
                <div id="contenido-detalle-<?= $row['fecha'] ?>">Cargando...</div>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

<script>
    function mostrarDetalle(fecha) {
        const fila = document.getElementById("detalle-" + fecha);
        const contenedor = document.getElementById("contenido-detalle-" + fecha);

        if (fila.style.display === "none") {
            fetch("detalle_ventas.php?fecha=" + fecha)
                .then(res => res.text())
                .then(html => {
                    contenedor.innerHTML = html;
                    fila.style.display = "";
                });
        } else {
            fila.style.display = "none";
        }
    }
</script>

<?php include "../includes/footer.php"; ?>