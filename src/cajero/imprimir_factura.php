<?php
// imprimir_factura.php — Generador de PDF de factura profesional
require '../includes/fpdf/fpdf.php';
require '../includes/db.php';

if (!isset($_GET['id'])) {
    die('ID de factura no proporcionado.');
}

$id = (int) $_GET['id'];

// Consulta de datos generales de la factura
$sql_factura = $conn->prepare("SELECT f.num_factura, f.fecha, c.nombre, c.apellido, m.nombre as metodo_pago FROM factura f JOIN cliente c ON f.id_cliente = c.id_cliente JOIN modo_pago m ON f.num_pago = m.num_pago WHERE f.num_factura = ?");
$sql_factura->bind_param("i", $id);
$sql_factura->execute();
$result_factura = $sql_factura->get_result();
$factura = $result_factura->fetch_assoc();

if (!$factura) {
    die('Factura no encontrada.');
}

// Consulta de los productos asociados a la factura
$sql_detalle = $conn->prepare("SELECT p.nombre, d.cantidad, d.precio FROM detalle d JOIN producto p ON d.id_producto = p.id_producto WHERE d.id_factura = ?");
$sql_detalle->bind_param("i", $id);
$sql_detalle->execute();
$result_detalle = $sql_detalle->get_result();

// Crear instancia PDF
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 20);

// Logo o nombre empresa (opcional)
$pdf->SetFont('Arial', 'B', 18);
$pdf->SetTextColor(40, 40, 40);
$pdf->Cell(0, 10, 'Mi Empresa S.A.S.', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'NIT: 900123456-7', 0, 1, 'C');
$pdf->Cell(0, 8, 'Direccion: Calle 123 # 45-67, Ciudad', 0, 1, 'C');
$pdf->Cell(0, 8, 'Telefono: (601) 555-1234 | Email: contacto@miempresa.com', 0, 1, 'C');
$pdf->Ln(10);

// Datos factura
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'FACTURA No. ' . $factura['num_factura'], 0, 1, 'L');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(100, 8, 'Cliente: ' . mb_convert_encoding($factura['nombre'] . ' ' . $factura['apellido'], 'ISO-8859-1', 'UTF-8'), 0, 0);
$pdf->Cell(90, 8, 'Fecha: ' . $factura['fecha'], 0, 1);
$pdf->Cell(100, 8, 'Metodo de Pago: ' . mb_convert_encoding($factura['metodo_pago'], 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->Ln(8);

// Encabezado de la tabla de productos
$pdf->SetFillColor(52, 152, 219);
$pdf->SetTextColor(255);
$pdf->SetDrawColor(41, 128, 185);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(80, 10, 'Producto', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Cantidad', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Precio Unitario', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Subtotal', 1, 1, 'C', true);

// Detalles de productos
$total = 0;
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(0);
while ($row = $result_detalle->fetch_assoc()) {
    $nombre = mb_convert_encoding($row['nombre'], 'ISO-8859-1', 'UTF-8');
    $cantidad = $row['cantidad'];
    $precio = number_format($row['precio'], 2);
    $subtotal = $row['cantidad'] * $row['precio'];
    $total += $subtotal;

    $pdf->Cell(80, 8, $nombre, 1);
    $pdf->Cell(30, 8, $cantidad, 1, 0, 'C');
    $pdf->Cell(40, 8, '$' . number_format($row['precio'], 2), 1, 0, 'R');
    $pdf->Cell(40, 8, '$' . number_format($subtotal, 2), 1, 1, 'R');
}

// Total
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(150, 10, 'TOTAL A PAGAR', 1, 0, 'R');
$pdf->Cell(40, 10, '$' . number_format($total, 2), 1, 1, 'R');

// Footer (opcional)
$pdf->Ln(10);
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 10, mb_convert_encoding('Gracias por su compra.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');


$pdf->Output('I', 'Factura_' . $factura['num_factura'] . '.pdf');
exit;
