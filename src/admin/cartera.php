<?php
include "../includes/db.php";
include "../includes/auth.php";
include "../includes/header.php";
verificarRol("ADMINISTRADOR");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente = intval($_POST['id_cliente']);
    $monto_total = floatval($_POST['monto_total']);
    $plazo_meses = intval($_POST['plazo_meses']);
    $tipo_pago = $_POST['tipo_pago'];
    $fecha_inicio = date('Y-m-d');
    
    // Validaciones
    if ($plazo_meses < 3 || $plazo_meses > 10) {
        die("El plazo debe ser entre 3 y 10 meses");
    }
    
    $tasa_interes = ($tipo_pago == 'mensual') ? 0.02 : 0.01;
    
    // Insertar crédito
    $stmt = $conn->prepare("INSERT INTO creditos (id_cliente, monto_total, plazo_meses, tipo_pago, fecha_inicio, estado) VALUES (?, ?, ?, ?, ?, 'activo')");
    $stmt->bind_param("idiss", $id_cliente, $monto_total, $plazo_meses, $tipo_pago, $fecha_inicio);
    $stmt->execute();
    $id_credito = $conn->insert_id;
    
    // Calcular cuotas
    $monto_cuota_capital = $monto_total / $plazo_meses;
    $intervalo = ($tipo_pago == 'mensual') ? 1 : 2;
    
    for ($i = 1; $i <= $plazo_meses; $i++) {
        $fecha_vencimiento = date('Y-m-d', strtotime($fecha_inicio . " + " . ($i * $intervalo) . " months"));
        $monto_interes = $monto_total * $tasa_interes;
        
        $stmt = $conn->prepare("INSERT INTO cuotas (id_credito, numero_cuota, monto_capital, monto_interes, fecha_vencimiento, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
        $stmt->bind_param("iidds", $id_credito, $i, $monto_cuota_capital, $monto_interes, $fecha_vencimiento);
        $stmt->execute();
    }
    
    header("Location: ver_credito.php?id=" . $id_credito);
    exit();
}
?>

<div class="container mt-4">
    <h2>Nuevo Crédito</h2>

    <form method="POST" class="mt-4">
        <div class="form-group mb-3">
            <label>Cliente:</label>
            <select name="id_cliente" class="form-control" required>
                <?php
                $clientes = $conn->query("SELECT id_cliente, CONCAT(nombre, ' ', apellido) as nombre FROM cliente");
                while ($cliente = $clientes->fetch_assoc()) {
                    echo "<option value='{$cliente['id_cliente']}'>{$cliente['nombre']}</option>";
                }
                ?>
            </select>
        </div>
        
        <div class="form-group mb-3">
            <label>Monto Total:</label>
            <input type="number" name="monto_total" step="0.01" class="form-control" required>
        </div>
        
        <div class="form-group mb-3">
            <label>Plazo (meses):</label>
            <select name="plazo_meses" class="form-control" required>
                <?php for ($i = 3; $i <= 10; $i++) { ?>
                    <option value="<?= $i ?>"><?= $i ?> meses</option>
                <?php } ?>
            </select>
        </div>
        
        <div class="form-group mb-3">
            <label>Tipo de Pago:</label>
            <select name="tipo_pago" class="form-control" required>
                <option value="mensual">Mensual (2% interés)</option>
                <option value="bimensual">Bimensual (1% interés)</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">Crear Crédito</button>
    </form>
</div>

<?php include "../includes/footer.php"; ?>