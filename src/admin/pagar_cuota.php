<?php
include "../includes/db.php";
include "../includes/auth.php";
include "../includes/header.php";
verificarRol("ADMINISTRADOR");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cuotas = isset($_POST['cuotas']) ? $_POST['cuotas'] : [];
    $id_credito = isset($_POST['id_credito']) ? intval($_POST['id_credito']) : 0;
    if (empty($cuotas) || !$id_credito) {
        $_SESSION['error'] = "No se seleccionaron cuotas válidas.";
        header("Location: ver_credito.php?id=" . $id_credito);
        exit();
    }
    $fecha_actual = date('Y-m-d');
    if (!defined('FPDF_FONTPATH')) {
        define('FPDF_FONTPATH', __DIR__ . '/../includes/fpdf/font/');
    }
    require_once '../includes/fpdf.php';
    $metodo_pago = isset($_POST['metodo_pago']) ? $_POST['metodo_pago'] : 'N/A';
    $tirilla_paths = [];
    foreach ($cuotas as $id_cuota) {
        $id_cuota = intval($id_cuota);
        // Verificar si hay cuotas anteriores sin pagar
        $stmt = $conn->prepare("SELECT c1.numero_cuota, c1.monto_capital, c1.monto_interes, c1.id_credito, c1.estado,
            (SELECT COUNT(*) FROM cuotas c2 
             WHERE c2.id_credito = ? 
             AND c2.numero_cuota < c1.numero_cuota 
             AND c2.estado != 'pagado') as cuotas_pendientes
            FROM cuotas c1 
            WHERE c1.id_cuota = ?");
        $stmt->bind_param("ii", $id_credito, $id_cuota);
        $stmt->execute();
        $result = $stmt->get_result();
        $cuota = $result->fetch_assoc();
        if ($cuota['cuotas_pendientes'] > 0) {
            continue; // No pagar si hay cuotas anteriores sin pagar
        }
        // Actualizar estado de la cuota
        $stmt = $conn->prepare("UPDATE cuotas SET estado = 'pagado', fecha_pago = ? WHERE id_cuota = ?");
        $stmt->bind_param("si", $fecha_actual, $id_cuota);
        $stmt->execute();

        // Generar tirilla PDF
        $stmt = $conn->prepare("SELECT cr.id_credito, cr.monto_total, cr.plazo_meses, cr.tipo_pago, cl.nombre, cl.apellido FROM creditos cr JOIN cliente cl ON cr.id_cliente = cl.id_cliente WHERE cr.id_credito = ?");
        $stmt->bind_param("i", $id_credito);
        $stmt->execute();
        $res = $stmt->get_result();
        $credito = $res->fetch_assoc();

    // Obtener datos actualizados de la cuota pagada
    $stmt = $conn->prepare("SELECT numero_cuota, monto_capital, monto_interes, estado, fecha_pago FROM cuotas WHERE id_cuota = ?");
    $stmt->bind_param("i", $id_cuota);
    $stmt->execute();
    $res_cuota = $stmt->get_result();
    $cuota_actualizada = $res_cuota->fetch_assoc();

    $tirilla = new FPDF();
    $tirilla->AddPage();
    $tirilla->SetFont('Arial','B',15);
    $tirilla->Cell(0,12,'Tirilla de Pago de Cuota',0,1,'C');
    $tirilla->SetFont('Arial','',12);
    $tirilla->Cell(0,8,'Cliente: ' . $credito['nombre'] . ' ' . $credito['apellido'],0,1);
    $tirilla->Cell(0,8,'Credito ID: ' . $credito['id_credito'],0,1);
    $tirilla->Cell(0,8,'Cuota No: ' . $cuota_actualizada['numero_cuota'],0,1);
    $tirilla->Cell(0,8,'Valor Pagado: $' . number_format($cuota_actualizada['monto_capital'] + $cuota_actualizada['monto_interes'],2),0,1);
    $tirilla->Cell(0,8,'Monto Capital: $' . number_format($cuota_actualizada['monto_capital'],2),0,1);
    $tirilla->Cell(0,8,'Monto Interes: $' . number_format($cuota_actualizada['monto_interes'],2),0,1);
    $tirilla->Cell(0,8,'Metodo de Pago: ' . $metodo_pago,0,1);
    $tirilla->Cell(0,8,'Fecha de Pago: ' . ($cuota_actualizada['fecha_pago'] ? date('d/m/Y', strtotime($cuota_actualizada['fecha_pago'])) : date('d/m/Y')),0,1);
    $tirilla->Cell(0,8,'Hora de Pago: ' . date('H:i:s'),0,1);
    // Calcular valor restante
    $stmt = $conn->prepare("SELECT SUM(monto_capital + monto_interes) as restante FROM cuotas WHERE id_credito = ? AND estado != 'pagado'");
    $stmt->bind_param("i", $id_credito);
    $stmt->execute();
    $res = $stmt->get_result();
    $restante = $res->fetch_assoc();
    $tirilla->Cell(0,8,'Valor Restante: $' . number_format($restante['restante'],2),0,1);
    $tirilla->Cell(0,8,'Usuario: ' . (isset($_SESSION['correo']) ? $_SESSION['correo'] : 'N/A'),0,1);
        $tirilla_dir = realpath(__DIR__ . '/../tirillas');
        if (!$tirilla_dir) {
            mkdir(__DIR__ . '/../tirillas', 0777, true);
            $tirilla_dir = realpath(__DIR__ . '/../tirillas');
        }
        $tirilla_path = $tirilla_dir . '/tirilla_pago_' . $id_cuota . '_' . time() . '.pdf';
        $tirilla->Output('F', $tirilla_path);
        $tirilla_paths[] = $tirilla_path;
        // Guardar ruta en la cuota (requiere campo tirilla_pago en la tabla cuotas)
        $stmt = $conn->prepare("UPDATE cuotas SET tirilla_pago = ? WHERE id_cuota = ?");
        $stmt->bind_param("si", $tirilla_path, $id_cuota);
        $stmt->execute();
    }
    // Verificar si todas las cuotas están pagadas
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cuotas WHERE id_credito = ? AND estado != 'pagado'");
    $stmt->bind_param("i", $id_credito);
    $stmt->execute();
    $result = $stmt->get_result();
    $pendientes = $result->fetch_assoc();
    // Si no hay cuotas pendientes, actualizar estado del crédito
    if ($pendientes['total'] == 0) {
        $stmt = $conn->prepare("UPDATE creditos SET estado = 'cancelado' WHERE id_credito = ?");
        $stmt->bind_param("i", $id_credito);
        $stmt->execute();
    }
    header("Location: ver_credito.php?id=" . $id_credito);
    exit();
}
?>