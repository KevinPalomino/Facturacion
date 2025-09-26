<?php
include "../includes/db.php";
include "../includes/auth.php";
include "../includes/header.php";
verificarRol("ADMINISTRADOR");

$id_credito = intval($_GET['id']);

// Obtener información del crédito
$stmt = $conn->prepare("SELECT c.*, CONCAT(cl.nombre, ' ', cl.apellido) as nombre_cliente 
                       FROM creditos c 
                       JOIN cliente cl ON c.id_cliente = cl.id_cliente 
                       WHERE c.id_credito = ?");
$stmt->bind_param("i", $id_credito);
$stmt->execute();
$credito = $stmt->get_result()->fetch_assoc();

// Obtener cuotas
$stmt = $conn->prepare("SELECT * FROM cuotas WHERE id_credito = ? ORDER BY numero_cuota");
$stmt->bind_param("i", $id_credito);
$stmt->execute();
$cuotas = $stmt->get_result();
?>

<div class="container mt-4">
    <h2>Extracto de Crédito</h2>
    <div class="card info-credito mb-4">
        <div class="card-body">
            <p><strong>Cliente:</strong> <?= htmlspecialchars($credito['nombre_cliente']) ?></p>
            <p><strong>Monto Total:</strong> $<?= number_format($credito['monto_total'], 2) ?></p>
            <p><strong>Plazo:</strong> <?= $credito['plazo_meses'] ?> meses</p>
            <p><strong>Tipo de Pago:</strong> <?= ucfirst($credito['tipo_pago']) ?></p>
        </div>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Cuota</th>
                    <th>Capital</th>
                    <th>Interés</th>
                    <th>Total Cuota</th>
                    <th>Fecha Vencimiento</th>
                    <th>Estado</th>
                    <th>Días Restantes</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Verificar cuál es la siguiente cuota a pagar
                $siguiente_cuota = null;
                $cuotas_array = [];
                while ($c = $cuotas->fetch_assoc()) {
                    $cuotas_array[] = $c;
                    if ($c['estado'] != 'pagado' && $siguiente_cuota === null) {
                        $siguiente_cuota = $c['numero_cuota'];
                    }
                }

                foreach ($cuotas_array as $cuota):
                    $total_cuota = $cuota['monto_capital'] + $cuota['monto_interes'];
                    $hoy = new DateTime();
                    $vencimiento = new DateTime($cuota['fecha_vencimiento']);
                    $diff = $hoy->diff($vencimiento);
                    $dias_restantes = $vencimiento > $hoy ? $diff->days : -$diff->days;
                    
                    // Definir clase según estado y tiempo restante
                    if ($cuota['estado'] == 'pagado') {
                        $clase = 'table-success';
                        $estado_texto = '✓ Pagado';
                    } else {
                        if ($vencimiento < $hoy) {
                            $clase = 'table-danger';
                            $estado_texto = '⚠ Vencida';
                        } elseif ($dias_restantes <= 5) {
                            $clase = 'table-warning';
                            $estado_texto = '⚠ Próxima a vencer';
                        } else {
                            $clase = 'table-light';
                            $estado_texto = 'Pendiente';
                        }
                    }

                    // Si no es la siguiente cuota a pagar, deshabilitar el botón
                    $boton_habilitado = ($cuota['estado'] != 'pagado' && $cuota['numero_cuota'] == $siguiente_cuota);
                ?>
                    <tr class="<?= $clase ?>">
                        <td><?= $cuota['numero_cuota'] ?></td>
                        <td>$<?= number_format($cuota['monto_capital'], 2) ?></td>
                        <td>$<?= number_format($cuota['monto_interes'], 2) ?></td>
                        <td>$<?= number_format($total_cuota, 2) ?></td>
                        <td><?= date('d/m/Y', strtotime($cuota['fecha_vencimiento'])) ?></td>
                        <td><?= $estado_texto ?></td>
                        <td><?= $dias_restantes >= 0 ? $dias_restantes . ' días' : 'Vencida' ?></td>
                        <td>
                            <?php if ($cuota['estado'] != 'pagado'): ?>
                                <form method="POST" action="pagar_cuota.php">
                                    <input type="hidden" name="id_cuota" value="<?= $cuota['id_cuota'] ?>">
                                    <button type="submit" 
                                            class="btn btn-sm <?= $boton_habilitado ? 'btn-success' : 'btn-secondary' ?>" 
                                            <?= $boton_habilitado ? '' : 'disabled' ?>
                                            title="<?= $boton_habilitado ? 'Pagar cuota' : 'Debe pagar las cuotas anteriores primero' ?>">
                                        Pagar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>