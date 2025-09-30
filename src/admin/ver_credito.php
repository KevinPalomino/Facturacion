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

if (!$credito) {
    echo '<div class="container mt-4"><div class="alert alert-danger">El crédito solicitado no existe o fue eliminado.</div></div>';
    include "../includes/footer.php";
    exit;
}

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
    <form id="form-pago-cuotas" method="POST" action="pagar_cuota.php" style="width:100%;max-width:1600px;margin:auto;padding:clamp(10px,3vw,32px);background:#fff;border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.07);display:flex;flex-wrap:wrap;gap:32px;align-items:flex-start;">
    <div style="flex:2 1 700px;min-width:340px;">
    <table class="table table-bordered align-middle text-center" style="width:100%;margin:auto;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.08);table-layout:fixed;word-break:break-word;">
            <thead class="table-dark" style="background:#1976d2;color:#fff;font-size:clamp(14px,2vw,17px);">
                <tr>
                    <th></th>
                    <th>Cuota</th>
                    <th>Capital</th>
                    <th>Interés</th>
                    <th>Total Cuota</th>
                    <th>Fecha Vencimiento</th>
                    <th>Estado</th>
                    <th>Días Restantes</th>
                </tr>
            </thead>
            <tbody style="font-size:clamp(13px,1.8vw,16px);">
                <?php 
                $cuotas_array = [];
                while ($c = $cuotas->fetch_assoc()) {
                    $cuotas_array[] = $c;
                }
                foreach ($cuotas_array as $cuota):
                    $total_cuota = $cuota['monto_capital'] + $cuota['monto_interes'];
                    $hoy = new DateTime();
                    $vencimiento = new DateTime($cuota['fecha_vencimiento']);
                    $diff = $hoy->diff($vencimiento);
                    $dias_restantes = $vencimiento > $hoy ? $diff->days : -$diff->days;
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
                ?>
                    <tr class="<?= $clase ?>" style="border-bottom:1px solid #e0e0e0;">
                        <td>
                            <?php if ($cuota['estado'] != 'pagado'): ?>
                                <input type="checkbox" name="cuotas[]" value="<?= $cuota['id_cuota'] ?>" class="cuota-check" />
                            <?php endif; ?>
                        </td>
                        <td><?= $cuota['numero_cuota'] ?></td>
                        <td>$<?= number_format($cuota['monto_capital'], 2) ?></td>
                        <td>$<?= number_format($cuota['monto_interes'], 2) ?></td>
                        <td>$<?= number_format($total_cuota, 2) ?></td>
                        <td><?= date('d/m/Y', strtotime($cuota['fecha_vencimiento'])) ?></td>
                        <td><?= $estado_texto ?>
                            <?php if (!empty($cuota['tirilla_pago'])): ?>
                                <div style="margin-top:8px;">
                                    <a href="<?= str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', $cuota['tirilla_pago']) ?>" target="_blank" style="display:inline-block;padding:6px 18px;font-size:15px;border-radius:8px;background:#1976d2;color:#fff;text-decoration:none;box-shadow:0 2px 8px rgba(0,0,0,0.08);transition:background 0.2s;">Descargar tirilla</a>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?= $dias_restantes >= 0 ? $dias_restantes . ' días' : 'Vencida' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="row mb-3">
            <div class="col-md-6 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary" id="btn-pagar-seleccion">Pagar cuotas seleccionadas</button>
                <button type="button" class="btn btn-warning" id="btn-pagar-todo">Cancelar toda la deuda</button>
            </div>
            <div class="col-md-6 text-end">
                <span id="resumen-pago" class="fw-bold"></span>
            </div>
        </div>
        </div>
        <!-- Panel lateral derecho -->
        <aside id="panel-deuda" style="flex:1 1 320px;min-width:260px;max-width:370px;background:#f7f9fc;border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,0.06);padding:24px 18px;margin-top:8px;">
            <h4 style="color:#1976d2;font-weight:bold;margin-bottom:18px;">Resumen de Deuda</h4>
            <div style="font-size:16px;line-height:1.7;">
                <div><strong>Monto original:</strong> $<span id="monto-original"><?= number_format($credito['monto_total'],2) ?></span></div>
                <div><strong>Monto pagado:</strong> $<span id="monto-pagado">0.00</span></div>
                <div><strong>Saldo actual:</strong> $<span id="saldo-actual">0.00</span></div>
                <div><strong>Intereses acumulados:</strong> $<span id="interes-acumulado">0.00</span></div>
                <div id="descuento-total" style="color:#388e3c;font-weight:bold;display:none;"></div>
                <div style="margin-top:12px;"><strong>Total a pagar:</strong> $<span id="total-pagar">0.00</span></div>
            </div>
            <hr style="margin:18px 0;">
            <div id="panel-metodo-pago">
                <h5 style="color:#1976d2;font-weight:bold;margin-bottom:10px;">Selecciona el método de pago</h5>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <label style="display:flex;align-items:center;gap:8px;padding:8px 0;cursor:pointer;border-radius:8px;transition:background 0.2s;">
                        <input type="radio" name="metodo_pago_select" value="Efectivo" style="accent-color:#1976d2;" checked>
                        <span style="font-size:16px;">Efectivo</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;padding:8px 0;cursor:pointer;border-radius:8px;transition:background 0.2s;">
                        <input type="radio" name="metodo_pago_select" value="Tarjeta" style="accent-color:#1976d2;">
                        <span style="font-size:16px;">Tarjeta</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;padding:8px 0;cursor:pointer;border-radius:8px;transition:background 0.2s;">
                        <input type="radio" name="metodo_pago_select" value="Transferencia" style="accent-color:#1976d2;">
                        <span style="font-size:16px;">Transferencia</span>
                    </label>
                </div>
            </div>
        </aside>
        <input type="hidden" name="id_credito" value="<?= $id_credito ?>">
        <input type="hidden" name="accion" id="accion-pago" value="">
        <input type="hidden" name="metodo_pago" id="metodo-pago" value="">
        </form>
    <script>
        // JS para calcular el total y mostrar resumen y panel lateral
        const checkboxes = document.querySelectorAll('.cuota-check');
        const resumen = document.getElementById('resumen-pago');
        const btnPagarSeleccion = document.getElementById('btn-pagar-seleccion');
        const btnPagarTodo = document.getElementById('btn-pagar-todo');
        let cuotasArray = <?php echo json_encode($cuotas_array); ?>;
        const montoOriginal = <?= $credito['monto_total'] ?>;
        const panelDeuda = document.getElementById('panel-deuda');
        const montoPagadoSpan = document.getElementById('monto-pagado');
        const saldoActualSpan = document.getElementById('saldo-actual');
        const interesAcumuladoSpan = document.getElementById('interes-acumulado');
        const totalPagarSpan = document.getElementById('total-pagar');
        const descuentoTotalDiv = document.getElementById('descuento-total');

        function actualizarPanelDeuda() {
            let pagado = 0;
            let pendiente = 0;
            let interesAcumulado = 0;
            let interesFuturo = 0;
            let seleccionadas = [];
            let totalSeleccion = 0;
            let cuotasPendientes = 0;
            cuotasArray.forEach((cuota, i) => {
                if (cuota.estado === 'pagado') {
                    pagado += parseFloat(cuota.monto_capital) + parseFloat(cuota.monto_interes);
                    interesAcumulado += parseFloat(cuota.monto_interes);
                } else {
                    pendiente += parseFloat(cuota.monto_capital) + parseFloat(cuota.monto_interes);
                    interesFuturo += parseFloat(cuota.monto_interes);
                    if (checkboxes[i] && checkboxes[i].checked) {
                        seleccionadas.push(cuota.numero_cuota);
                        totalSeleccion += parseFloat(cuota.monto_capital) + parseFloat(cuota.monto_interes);
                        cuotasPendientes++;
                    }
                }
            });
            montoPagadoSpan.textContent = pagado.toFixed(2);
            saldoActualSpan.textContent = (montoOriginal - pagado).toFixed(2);
            interesAcumuladoSpan.textContent = interesAcumulado.toFixed(2);
            totalPagarSpan.textContent = totalSeleccion.toFixed(2);
            // Si selecciona todas las cuotas pendientes, mostrar descuento
            if (cuotasPendientes > 0 && cuotasPendientes === cuotasArray.filter(c=>c.estado!=='pagado').length) {
                descuentoTotalDiv.style.display = 'block';
                descuentoTotalDiv.textContent = `Descuento por intereses futuros: $${interesFuturo.toFixed(2)}`;
            } else {
                descuentoTotalDiv.style.display = 'none';
            }
        }
        function calcularTotalSeleccionado() {
            let total = 0;
            let seleccionadas = [];
            checkboxes.forEach((cb, i) => {
                if (cb.checked) {
                    total += parseFloat(cuotasArray[i].monto_capital) + parseFloat(cuotasArray[i].monto_interes);
                    seleccionadas.push(cuotasArray[i].numero_cuota);
                }
            });
            resumen.textContent = seleccionadas.length ? `Total a pagar por cuotas [${seleccionadas.join(', ')}]: $${total.toFixed(2)}` : '';
            actualizarPanelDeuda();
            return total;
        }
        checkboxes.forEach(cb => cb.addEventListener('change', calcularTotalSeleccionado));
        window.addEventListener('DOMContentLoaded', () => {
            calcularTotalSeleccionado();
        });
        function obtenerMetodoPago() {
            const radios = document.getElementsByName('metodo_pago_select');
            let metodo = '';
            radios.forEach(r => { if (r.checked) metodo = r.value; });
            return metodo;
        }
        btnPagarSeleccion.onclick = function() {
            document.getElementById('accion-pago').value = 'seleccion';
            document.getElementById('metodo-pago').value = obtenerMetodoPago();
            document.getElementById('form-pago-cuotas').submit();
        };
        btnPagarTodo.onclick = function() {
            checkboxes.forEach(cb => cb.checked = true);
            calcularTotalSeleccionado();
            document.getElementById('accion-pago').value = 'total';
            document.getElementById('metodo-pago').value = obtenerMetodoPago();
            if (confirm('¿Está seguro que desea cancelar toda la deuda? Esta acción pagará todas las cuotas pendientes.')) {
                document.getElementById('form-pago-cuotas').submit();
            }
        };
        </script>
    </div>
</div>

<?php include "../includes/footer.php"; ?>