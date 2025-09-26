<?php
// nueva_factura.php
session_start();
include "../includes/auth.php";
verificarRol("CAJERO");
include "../includes/db.php";
include "../includes/header.php";


// Inicializar la sesión de la factura si no existe
if (!isset($_SESSION['factura'])) {
    $_SESSION['factura'] = [];
}

$mostrar_boton_imprimir = false;
$num_factura = null;



// Manejo de las acciones del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Agregar producto a la factura
    if (isset($_POST['agregar'])) {
        $id_producto = (int)$_POST['id_producto'];
        $cantidad = (int)$_POST['cantidad'];

        $query = $conn->prepare("SELECT nombre, precio, stock FROM producto WHERE id_producto = ?");
        $query->bind_param("i", $id_producto);
        $query->execute();
        $query->bind_result($nombre, $precio, $stock);
        $query->fetch();
        $query->close();

        if ($cantidad > 0 && $cantidad <= $stock) {
            $_SESSION['factura'][$id_producto] = [
                'nombre' => $nombre,
                'precio' => $precio,
                'cantidad' => $cantidad,
                'stock' => $stock
            ];
        }
    }

    // Eliminar producto de la factura
    if (isset($_POST['eliminar'])) {
        $id_producto = (int)$_POST['id_producto'];
        unset($_SESSION['factura'][$id_producto]);
    }

    // Actualizar cantidad de un producto en la factura
    if (isset($_POST['actualizar'])) {
        $id_producto = (int)$_POST['id_producto'];
        $cantidad = (int)$_POST['cantidad'];

        if ($cantidad > 0 && $cantidad <= $_SESSION['factura'][$id_producto]['stock']) {
            $_SESSION['factura'][$id_producto]['cantidad'] = $cantidad;
        }
    }

    // Guardar la factura completa
    if (isset($_POST['guardar_factura'])) {
        $id_cliente = $_POST['id_cliente'];
        $num_pago = $_POST['num_pago'];
        $productos = $_SESSION['factura'];

        // Obtener información del método de pago
        $stmt = $conn->prepare("SELECT nombre FROM modo_pago WHERE num_pago = ?");
        $stmt->bind_param("i", $num_pago);
        $stmt->execute();
        $result = $stmt->get_result();
        $modo_pago = $result->fetch_assoc();
        $es_credito = strtolower($modo_pago['nombre']) === 'Credito';

        // Validar stock disponible
        foreach ($productos as $id_producto => $datos) {
            $cantidad = (int)$datos['cantidad'];

            $stmt = $conn->prepare("SELECT stock FROM producto WHERE id_producto = ?");
            $stmt->bind_param("i", $id_producto);
            $stmt->execute();
            $stmt->bind_result($stock_actual);
            $stmt->fetch();
            $stmt->close();

            if ($cantidad > $stock_actual) {
                echo "<p style='color:red'>Error: Stock insuficiente para el producto {$datos['nombre']}.</p>";
                exit;
            }
        }

        // Insertar la factura
        $stmt = $conn->prepare("INSERT INTO factura (id_cliente, fecha, num_pago) VALUES (?, NOW(), ?)");
        $stmt->bind_param("ii", $id_cliente, $num_pago);
        $stmt->execute();
        $num_factura = $stmt->insert_id;
        $stmt->close();

        // Si es crédito, crear el registro de crédito y las cuotas
        if ($es_credito) {
            // Calcular el total de la factura
            $monto_total = 0;
            foreach ($productos as $datos) {
                $monto_total += $datos['precio'] * $datos['cantidad'];
            }

            // Obtener información del crédito del formulario
            $plazo_meses = $_POST['plazo_meses'];
            $tipo_pago = $_POST['tipo_pago'];
            $tasa_interes = $tipo_pago == 'bimensual' ? 0.01 : 0.02;

            // Insertar el crédito
            $stmt = $conn->prepare("INSERT INTO creditos (id_cliente, monto_total, plazo_meses, tipo_pago, fecha_inicio, estado) VALUES (?, ?, ?, ?, NOW(), 'activo')");
            $stmt->bind_param("idis", $id_cliente, $monto_total, $plazo_meses, $tipo_pago);
            $stmt->execute();
            $id_credito = $stmt->insert_id;

            // Calcular e insertar las cuotas
            $monto_cuota_capital = $monto_total / $plazo_meses;
            $intervalo = $tipo_pago == 'bimensual' ? 2 : 1;

            for ($i = 1; $i <= $plazo_meses; $i++) {
                $fecha_vencimiento = date('Y-m-d', strtotime("+ " . ($i * $intervalo) . " months"));
                $monto_interes = $monto_total * $tasa_interes;

                $stmt = $conn->prepare("INSERT INTO cuotas (id_credito, numero_cuota, monto_capital, monto_interes, fecha_vencimiento, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
                $stmt->bind_param("iidds", $id_credito, $i, $monto_cuota_capital, $monto_interes, $fecha_vencimiento);
                $stmt->execute();
            }
        }

        // Insertar detalles e impactar el stock
        foreach ($productos as $id_producto => $datos) {
            $cantidad = (int)$datos['cantidad'];
            $precio = (float)$datos['precio'];

            $stmt = $conn->prepare("INSERT INTO detalle (id_factura, id_producto, cantidad, precio) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $num_factura, $id_producto, $cantidad, $precio);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE producto SET stock = stock - ? WHERE id_producto = ?");
            $stmt->bind_param("ii", $cantidad, $id_producto);
            $stmt->execute();
            $stmt->close();
        }

        $_SESSION['factura'] = [];
        echo "<p style='color:green'>Factura registrada correctamente.</p>";
        $mostrar_boton_imprimir = true;
    }
}

// Consultas iniciales para formularios
$clientes = $conn->query("SELECT id_cliente, nombre, apellido FROM cliente");
$pagos = $conn->query("SELECT num_pago, nombre FROM modo_pago");
$productos = $conn->query("SELECT id_producto, nombre, precio, stock FROM producto");
?>

<h2>Nueva Factura</h2>
<!-- Incluir script de crédito -->
<script src="../js/credito.js"></script>

<div class="factura-container">
    <form method="POST" class="cliente-form">
        <div class="input-group buscar-wrapper">
            <label for="buscar_cliente">Buscar cliente (ID):</label>
            <input type="text" id="buscar_cliente" name="buscar_cliente" autocomplete="off" placeholder="Ej. 1020">
            <div id="sugerencias_cliente" style="display:none;"></div>
        </div>

        <div class="input-group">
            <label for="nombre_cliente">Nombre del cliente:</label>
            <input type="text" id="nombre_cliente" name="nombre_cliente" readonly>
        </div>

        <div style="grid-column: 1 / -1;" class="input-group">
            <label for="num_pago">Método de Pago:</label>
            <select name="num_pago" id="metodo_pago" required onchange="mostrarOpcionesCredito()">
                <option value="">Seleccione</option>
                <?php while ($p = $pagos->fetch_assoc()): ?>
                    <?php $esCredito = (strtolower(trim($p['nombre'])) === 'credito'); ?>
                    <option value="<?= $p['num_pago'] ?>" 
                            data-es-credito="<?= $esCredito ? 'true' : 'false' ?>">
                        <?= $p['nombre'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div id="opciones_credito" style="display: none; grid-column: 1 / -1;" class="input-group">
            <div style="display: flex; gap: 20px; margin-top: 15px;">
                <div style="flex: 1;">
                    <label for="plazo_meses">Plazo en meses:</label>
                    <select name="plazo_meses" id="plazo_meses" class="form-control" onchange="actualizarInfoCredito()">
                        <?php for ($i = 3; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?> meses</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label for="tipo_pago">Tipo de pago:</label>
                    <select name="tipo_pago" id="tipo_pago" class="form-control" onchange="actualizarInfoCredito()">
                        <option value="mensual">Mensual (2% interés)</option>
                        <option value="bimensual">Bimensual (1% interés)</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="info_credito" style="display: none; grid-column: 1 / -1; margin-top: 15px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;">
            <h4>Información del Crédito</h4>
            <div class="credito-detalles">
                <p><strong>Monto total:</strong> <span id="monto_total">$0.00</span></p>
                <p><strong>Valor cuota:</strong> <span id="valor_cuota">$0.00</span></p>
                <p><strong>Interés por cuota:</strong> <span id="interes_cuota">$0.00</span></p>
                <p><strong>Total a pagar:</strong> <span id="total_pagar">$0.00</span></p>
            </div>
        </div>

        <!-- Campo oculto para el ID del cliente -->
        <select name="id_cliente" id="id_cliente" style="display:none;" required>
            <?php while ($c = $clientes->fetch_assoc()): ?>
                <option value="<?= $c['id_cliente'] ?>">
                    <?= $c['nombre'] . ' ' . $c['apellido'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <div style="grid-column: 1 / -1;">
            <input type="submit" name="guardar_factura" value="Guardar Factura">
            <?php if ($mostrar_boton_imprimir && $num_factura): ?>
                <a href="imprimir_factura.php?id=<?= $num_factura ?>" target="_blank" class="btn" style="margin-top:15px; display:inline-block;">Imprimir Factura</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Agregar productos -->
    <h3>Agregar Producto</h3>
    <form method="POST" id="form-producto">
        <!-- Buscador interactivo -->
        <label for="buscar_producto">Buscar producto:</label>
        <input type="text" id="buscar_producto" placeholder="Nombre o ID del producto" autocomplete="off">
        <div id="sugerencias_producto" style="position:relative; background:white; border:1px solid #ccc; display:none; max-height:150px; overflow-y:auto;"></div>

        <!-- Select con alertas -->
        <label for="id_producto">Seleccionar producto:</label>
        <select name="id_producto" id="id_producto" required>
            <option value="">Seleccione producto</option>
            <?php while ($pr = $productos->fetch_assoc()):
                $stock = $pr['stock'];
                $limite_bajo = max(1, round($stock * 0.2));
                $alerta = "";
                $clase_alerta = "";

                if ($stock == 0) {
                    $alerta = "⚠ SIN STOCK";
                    $clase_alerta = "sin-stock";
                } elseif ($stock <= $limite_bajo) {
                    $alerta = "⚠ STOCK BAJO";
                    $clase_alerta = "stock-bajo";
                }
            ?>
                <option
                    value="<?= $pr['id_producto'] ?>"
                    data-nombre="<?= $pr['nombre'] ?>"
                    class="<?= $clase_alerta ?>">
                    <?= $pr['nombre'] ?> (Stock: <?= $stock ?>, $<?= $pr['precio'] ?>) <?= $alerta ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="cantidad">Cantidad:</label>
        <input type="number" name="cantidad" min="1" value="1" required>

        <input type="submit" name="agregar" value="Agregar">
    </form>

    <!-- STYLE IN CODE, TOCA BORRARLO -->
    <style>
        .sin-stock {
            color: red;
            font-weight: bold;
        }

        .stock-bajo {
            color: orange;
            font-weight: bold;
        }
    </style>


    <!-- Estilos opcionales -->
    <style>
        .sin-stock {
            color: red;
            font-weight: bold;
        }

        .stock-bajo {
            color: orange;
        }
    </style>
    <!-- STYLE IN CODE, TOCA BORRARLO -->
    <!-- Script de validación -->
    <script>
        document.getElementById('form-producto').addEventListener('submit', function(e) {
            const select = document.getElementById('id_producto');
            const selectedOption = select.options[select.selectedIndex];
            const stock = parseInt(selectedOption.getAttribute('data-stock'));

            if (isNaN(stock)) return;

            if (stock === 0) {
                alert('❌ No puedes agregar este producto. No hay stock disponible.');
                e.preventDefault();
                return;
            }

            if (stock <= Math.max(1, Math.round(stock * 0.2))) {
                alert('⚠ Advertencia: Este producto tiene stock bajo. Considere reabastecer pronto.');
                // No evitamos agregar, solo advertimos
            }
        });
    </script>

    <!-- Detalle de la factura -->
    <h3>Detalle Factura</h3>
    <table border="1" cellpadding="5">
        <tr>
            <th>Producto</th>
            <th>Precio</th>
            <th>Cantidad</th>
            <th>Subtotal</th>
            <th>Acción</th>
        </tr>
        <?php
        $total = 0;
        foreach ($_SESSION['factura'] as $id => $item):
            $subtotal = $item['precio'] * $item['cantidad'];
            $total += $subtotal;
        ?>
            <tr>
                <td><?= $item['nombre'] ?></td>
                <td>$<?= number_format($item['precio'], 2) ?></td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="id_producto" value="<?= $id ?>">
                        <input type="number" name="cantidad" value="<?= $item['cantidad'] ?>" min="1" max="<?= $item['stock'] ?>">
                        <input type="submit" name="actualizar" value="Actualizar">
                    </form>
                </td>
                <td>$<?= number_format($subtotal, 2) ?></td>
                <td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="id_producto" value="<?= $id ?>">
                        <input type="submit" name="eliminar" value="Eliminar">
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <h3>Total: $<?= number_format($total, 2) ?></h3>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#buscar_cliente').on('keyup', function() {
            var query = $(this).val();
            if (query.length > 0) {
                $.ajax({
                    url: '../includes/buscar_clientes.php',
                    method: 'POST',
                    data: {
                        query: query
                    },
                    success: function(data) {
                        $('#sugerencias_cliente').fadeIn();
                        $('#sugerencias_cliente').html(data);
                    }
                });
            } else {
                $('#sugerencias_cliente').fadeOut();
            }
        });

        // Selección del cliente desde sugerencia
        $(document).on('click', '.cliente-sugerido', function() {
            var id = $(this).data('id');
            var nombre = $(this).text();

            $('#buscar_cliente').val(id);
            $('#sugerencias_cliente').fadeOut();
            $('#nombre_cliente').val(nombre);
            $('#id_cliente').val(id);
        });
    });
</script>

<script>
    $(document).ready(function() {
        $('#buscar_producto').on('keyup', function() {
            const query = $(this).val();
            if (query.length > 0) {
                $.ajax({
                    url: '../includes/buscar_productos.php',
                    method: 'POST',
                    data: {
                        query: query
                    },
                    success: function(data) {
                        $('#sugerencias_producto').fadeIn().html(data);
                    }
                });
            } else {
                $('#sugerencias_producto').fadeOut();
            }
        });

        // Al hacer clic en una sugerencia
        $(document).on('click', '.producto-sugerido', function() {
            const id = $(this).data('id');
            const nombre = $(this).data('nombre');

            $('#buscar_producto').val(nombre);
            $('#id_producto').val(id);
            $('#sugerencias_producto').fadeOut();
        });

        // Ocultar sugerencias si haces clic fuera
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#buscar_producto, #sugerencias_producto').length) {
                $('#sugerencias_producto').fadeOut();
            }
        });
    });
</script>