<?php
include "../includes/db.php";
include "../includes/auth.php";
include "../includes/header.php";
verificarRol("ADMINISTRADOR");

// Obtener todos los créditos con información del cliente
$query = "SELECT c.*, 
          CONCAT(cl.nombre, ' ', cl.apellido) as nombre_cliente,
          COUNT(cu.id_cuota) as total_cuotas,
          SUM(CASE WHEN cu.estado = 'pagado' THEN 1 ELSE 0 END) as cuotas_pagadas
          FROM creditos c
          JOIN cliente cl ON c.id_cliente = cl.id_cliente
          LEFT JOIN cuotas cu ON c.id_credito = cu.id_credito
          GROUP BY c.id_credito
          ORDER BY c.fecha_inicio DESC";

$creditos = $conn->query($query);
?>

<div class="container mt-4">
    <h2>Lista de Créditos</h2>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Cliente</th>
                    <th>Monto Total</th>
                    <th>Tipo Pago</th>
                    <th>Progreso</th>
                    <th>Estado</th>
                    <th>Fecha Inicio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($credito = $creditos->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($credito['nombre_cliente']) ?></td>
                        <td>$<?= number_format($credito['monto_total'], 2) ?></td>
                        <td><?= ucfirst($credito['tipo_pago']) ?></td>
                        <td>
                            <div class="progress">
                                <?php
                                $porcentaje = ($credito['total_cuotas'] > 0)
                                    ? ($credito['cuotas_pagadas'] / $credito['total_cuotas']) * 100
                                    : 0;
                                ?>
                                <div class="progress-bar" role="progressbar"
                                    style="width: <?= $porcentaje ?>%"
                                    aria-valuenow="<?= $porcentaje ?>"
                                    aria-valuemin="0"
                                    aria-valuemax="100">
                                    <?= $credito['cuotas_pagadas'] ?>/<?= $credito['total_cuotas'] ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php
                            $clase_estado = [
                                'activo' => 'text-success',
                                'cancelado' => 'text-primary',
                                'mora' => 'text-danger'
                            ];
                            ?>
                            <span class="<?= $clase_estado[$credito['estado']] ?>">
                                <?= ucfirst($credito['estado']) ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($credito['fecha_inicio'])) ?></td>
                        <td>
                            <a href="ver_credito.php?id=<?= $credito['id_credito'] ?>"
                                class="btn btn-info btn-sm">Ver Detalles</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>