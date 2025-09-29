<?php
// Test credit calculation
$monto_total = 1000000; // 1,000,000 test amount
$plazo_meses = 10;
$tipo_pago = 'bimensual';
$fecha_inicio = '2025-09-26';

echo "Test Credit Calculation\n";
echo "=====================\n";
echo "Total Amount: $" . number_format($monto_total, 2) . "\n";
echo "Term: $plazo_meses months\n";
echo "Payment Type: $tipo_pago\n";
echo "Start Date: $fecha_inicio\n\n";

// Calculate payments
$tasa_interes = $tipo_pago == 'bimensual' ? 0.01 : 0.02;
$num_cuotas = $tipo_pago == 'bimensual' ? ($plazo_meses * 2) : $plazo_meses;
$monto_cuota_capital = $monto_total / $num_cuotas;
$monto_interes = $monto_total * $tasa_interes;

echo "Payment Details\n";
echo "==============\n";
echo "Number of Payments: $num_cuotas\n";
echo "Interest Rate per Payment: " . ($tasa_interes * 100) . "%\n";
echo "Capital per Payment: $" . number_format($monto_cuota_capital, 2) . "\n";
echo "Interest per Payment: $" . number_format($monto_interes, 2) . "\n";
echo "Total per Payment: $" . number_format($monto_cuota_capital + $monto_interes, 2) . "\n\n";

echo "Payment Schedule\n";
echo "===============\n";

for ($i = 1; $i <= $num_cuotas; $i++) {
    if ($tipo_pago == 'bimensual') {
        $fecha_vencimiento = date('Y-m-d', strtotime($fecha_inicio . " + " . ($i * 15) . " days"));
    } else {
        $fecha_vencimiento = date('Y-m-d', strtotime($fecha_inicio . " + " . $i . " months"));
    }
    
    echo sprintf("Payment %2d: %s - $%s\n", 
        $i, 
        $fecha_vencimiento, 
        number_format($monto_cuota_capital + $monto_interes, 2)
    );
}

// Calculate total amount to be paid
$total_a_pagar = ($monto_cuota_capital + $monto_interes) * $num_cuotas;
echo "\nTotal to Pay: $" . number_format($total_a_pagar, 2) . "\n";
echo "Total Interest: $" . number_format($total_a_pagar - $monto_total, 2) . "\n";
?>