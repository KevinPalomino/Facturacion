<?php
include "../includes/db.php";
include "../includes/auth.php";
include "../includes/header.php";
verificarRol("ADMINISTRADOR");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cuota = intval($_POST['id_cuota']);
    
    // Verificar si hay cuotas anteriores sin pagar
    $stmt = $conn->prepare("SELECT c1.numero_cuota, c1.id_credito,
        (SELECT COUNT(*) FROM cuotas c2 
         WHERE c2.id_credito = c1.id_credito 
         AND c2.numero_cuota < c1.numero_cuota 
         AND c2.estado != 'pagado') as cuotas_pendientes
        FROM cuotas c1 
        WHERE c1.id_cuota = ?");
    $stmt->bind_param("i", $id_cuota);
    $stmt->execute();
    $result = $stmt->get_result();
    $cuota = $result->fetch_assoc();
    
    if ($cuota['cuotas_pendientes'] > 0) {
        $_SESSION['error'] = "No se puede pagar esta cuota. Debe pagar primero las cuotas anteriores.";
        header("Location: ver_credito.php?id=" . $cuota['id_credito']);
        exit();
    }
    
    // Actualizar estado de la cuota
    $fecha_actual = date('Y-m-d');
    $stmt = $conn->prepare("UPDATE cuotas SET estado = 'pagado', fecha_pago = ? WHERE id_cuota = ?");
    $stmt->bind_param("si", $fecha_actual, $id_cuota);
    $stmt->execute();
    
    // Obtener id_credito para redireccionar
    $stmt = $conn->prepare("SELECT id_credito FROM cuotas WHERE id_cuota = ?");
    $stmt->bind_param("i", $id_cuota);
    $stmt->execute();
    $result = $stmt->get_result();
    $credito = $result->fetch_assoc();
    
    // Verificar si todas las cuotas están pagadas
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cuotas WHERE id_credito = ? AND estado != 'pagado'");
    $stmt->bind_param("i", $credito['id_credito']);
    $stmt->execute();
    $result = $stmt->get_result();
    $pendientes = $result->fetch_assoc();
    
    // Si no hay cuotas pendientes, actualizar estado del crédito
    if ($pendientes['total'] == 0) {
        $stmt = $conn->prepare("UPDATE creditos SET estado = 'cancelado' WHERE id_credito = ?");
        $stmt->bind_param("i", $credito['id_credito']);
        $stmt->execute();
    }
    
    header("Location: ver_credito.php?id=" . $credito['id_credito']);
    exit();
}
?>