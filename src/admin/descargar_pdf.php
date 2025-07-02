<?php
ob_start();
require '../includes/ss/fpdf.php';
require '../includes/db.php';

$fecha = $_GET['fecha'] ?? '';
if (!$fecha) {
    die("⚠️ Fecha no proporcionada");
}

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, "Informe de Ventas Detallado - $fecha", 0, 1, 'C');
$pdf->Ln(5);

// Encabezado tabla
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(20, 10, 'Factura', 1);
$pdf->Cell(40, 10, 'Cliente', 1);
$pdf->Cell(45, 10, 'Producto', 1);
$pdf->Cell(20, 10, 'Cantidad', 1);
$pdf->Cell(30, 10, 'Precio', 1);
$pdf->Cell(35, 10, 'Subtotal', 1);
$pdf->Ln();

// Consulta con detalle por producto
$sql = "SELECT 
            f.num_factura,
            CONCAT(c.nombre, ' ', c.apellido) AS cliente,
            p.nombre AS producto,
            d.cantidad,
            d.precio,
            (d.cantidad * d.precio) AS subtotal
        FROM factura f
        JOIN cliente c ON f.id_cliente = c.id_cliente
        JOIN detalle d ON f.num_factura = d.id_factura
        JOIN producto p ON d.id_producto = p.id_producto
        WHERE DATE(f.fecha) = ?
        ORDER BY f.num_factura, cliente, producto";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $fecha);
$stmt->execute();
$resultado = $stmt->get_result();

$pdf->SetFont('Arial', '', 9);
$facturaAnterior = null;

while ($row = $resultado->fetch_assoc()) {
    $factura   = $row['num_factura'];
    $cliente   = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $row['cliente']);
    $producto  = iconv("UTF-8", "ISO-8859-1//TRANSLIT", $row['producto']);
    $cantidad  = $row['cantidad'];
    $precio    = number_format($row['precio'], 2);
    $subtotal  = number_format($row['subtotal'], 2);

    // Solo mostrar factura y cliente si es nueva factura
    $mostrarFactura = ($factura !== $facturaAnterior) ? $factura : '';
    $mostrarCliente = ($factura !== $facturaAnterior) ? $cliente : '';

    $pdf->Cell(20, 8, $mostrarFactura, 1);
    $pdf->Cell(40, 8, $mostrarCliente, 1);
    $pdf->Cell(45, 8, $producto, 1);
    $pdf->Cell(20, 8, $cantidad, 1, 0, 'C');
    $pdf->Cell(30, 8, "$$precio", 1, 0, 'R');
    $pdf->Cell(35, 8, "$$subtotal", 1, 0, 'R');
    $pdf->Ln();

    $facturaAnterior = $factura;
}

ob_end_clean();
$pdf->Output("I", "ventas_$fecha.pdf");
