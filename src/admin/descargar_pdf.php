<?php
ob_start(); // No debe haber nada de salida antes de esto
require '../includes/fpdf/fpdf.php';
require '../includes/db.php';

$fecha = $_GET['fecha'] ?? '';
if (!$fecha) {
    exit("⚠️ Fecha no proporcionada");
}

class PDF extends FPDF
{
    function Header()
    {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'EMPRESA XYZ S.A.S.'), 0, 1, 'C');
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Informe Detallado de Ventas'), 0, 1, 'C');
        $this->Ln(2);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 10, 'Fecha: ' . date('d/m/Y', strtotime($fecha)), 0, 1, 'R');
$pdf->Ln(2);

// Encabezado tabla
$pdf->SetFillColor(200, 220, 255);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(20, 10, 'Factura', 1, 0, 'C', true);
$pdf->Cell(40, 10, 'Cliente', 1, 0, 'C', true);
$pdf->Cell(45, 10, 'Producto', 1, 0, 'C', true);
$pdf->Cell(20, 10, 'Cantidad', 1, 0, 'C', true);
$pdf->Cell(30, 10, 'Precio', 1, 0, 'C', true);
$pdf->Cell(35, 10, 'Subtotal', 1, 1, 'C', true);

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
        ORDER BY cliente, f.num_factura";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $fecha);
$stmt->execute();
$resultado = $stmt->get_result();

$pdf->SetFont('Arial', '', 9);
$total_ventas = 0;
$clienteAnterior = null;
$fill = false;

while ($row = $resultado->fetch_assoc()) {
    $factura   = $row['num_factura'];
    $cliente   = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row['cliente']);
    $producto  = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $row['producto']);
    $cantidad  = $row['cantidad'];
    $precio    = number_format($row['precio'], 2);
    $subtotal  = number_format($row['subtotal'], 2);
    $total_ventas += $row['subtotal'];

    // Separador visual por cliente
    if ($cliente !== $clienteAnterior && $clienteAnterior !== null) {
        $pdf->Ln(2);
    }

    $mostrarFactura = ($cliente !== $clienteAnterior) ? $factura : '';
    $mostrarCliente = ($cliente !== $clienteAnterior) ? $cliente : '';

    $pdf->Cell(20, 8, $mostrarFactura, 1, 0, 'C', $fill);
    $pdf->Cell(40, 8, $mostrarCliente, 1, 0, 'L', $fill);
    $pdf->Cell(45, 8, $producto, 1, 0, 'L', $fill);
    $pdf->Cell(20, 8, $cantidad, 1, 0, 'C', $fill);
    $pdf->Cell(30, 8, "$$precio", 1, 0, 'R', $fill);
    $pdf->Cell(35, 8, "$$subtotal", 1, 1, 'R', $fill);

    $clienteAnterior = $cliente;
    $fill = !$fill;
}

// Total general
$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell(155, 10, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Total del Día'), 1, 0, 'R', true);
$pdf->Cell(35, 10, "$" . number_format($total_ventas, 2), 1, 1, 'R', true);

$pdf->Output("I", "informe_ventas_$fecha.pdf");
ob_end_flush();
