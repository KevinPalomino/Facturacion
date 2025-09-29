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
        $stmt = $conn->prepare("SELECT num_pago, nombre FROM modo_pago WHERE num_pago = ?");
        $stmt->bind_param("i", $num_pago);
        $stmt->execute();
        $result = $stmt->get_result();
        $modo_pago = $result->fetch_assoc();

        // Depuración del método de pago
        $nombre_modo_pago = trim($modo_pago['nombre']);
        echo "<pre>Método de pago seleccionado: ID=" . $modo_pago['num_pago'] . ", Nombre='" . htmlspecialchars($nombre_modo_pago) . "'</pre>";

        $es_credito = (strtolower($nombre_modo_pago) === 'credito');
        echo "<pre>¿Es crédito? " . ($es_credito ? 'Sí' : 'No') . "</pre>";

        if ($es_credito && $modo_pago['num_pago'] != 7) {
            echo "<pre style='color:red'>ERROR: El ID del método de pago crédito no coincide. Esperado: 7, Recibido: " . $modo_pago['num_pago'] . "</pre>";
            exit();
        }

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
            echo "<pre>Creando crédito...</pre>";
            // Calcular el total de la factura
            $monto_total = 0;
            foreach ($productos as $datos) {
                $monto_total += $datos['precio'] * $datos['cantidad'];
            }
            echo "<pre>Monto total calculado: $" . number_format($monto_total, 2) . "</pre>";

            // Obtener información del crédito del formulario
            $plazo_meses = $_POST['plazo_meses'];
            $tipo_pago = $_POST['tipo_pago'];
            $tasa_interes = $tipo_pago == 'bimensual' ? 0.01 : 0.02;

            // Insertar el crédito
            $stmt = $conn->prepare("INSERT INTO creditos (id_cliente, monto_total, plazo_meses, tipo_pago, fecha_inicio, estado) VALUES (?, ?, ?, ?, NOW(), 'activo')");
            $stmt->bind_param("idis", $id_cliente, $monto_total, $plazo_meses, $tipo_pago);

            if (!$stmt->execute()) {
                echo "<pre>Error al insertar crédito: " . $stmt->error . "</pre>";
                exit();
            }

            $id_credito = $stmt->insert_id;
            echo "<pre>Crédito insertado con ID: " . $id_credito . "</pre>";

            // Calcular e insertar las cuotas
            $num_cuotas = $tipo_pago == 'bimensual' ? ($plazo_meses * 2) : $plazo_meses;
            $monto_cuota_capital = $monto_total / $num_cuotas;

            for ($i = 1; $i <= $num_cuotas; $i++) {
                // Para pagos bimensuales, calcular cada 15 días
                if ($tipo_pago == 'bimensual') {
                    $fecha_vencimiento = date('Y-m-d', strtotime("+ " . ($i * 15) . " days"));
                } else {
                    $fecha_vencimiento = date('Y-m-d', strtotime("+ " . $i . " months"));
                }

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

        // Si es crédito, mostrar resumen de cuotas
        if ($es_credito) {
            echo "<div id='resumen-credito' class='resumen-container'>
                    <h3>Resumen de Crédito</h3>
                    <div class='resumen-detalles'>
                        <p><strong>Número de Factura:</strong> #$num_factura</p>
                        <p><strong>Monto Total:</strong> $" . number_format($monto_total, 2, ',', '.') . "</p>
                        <p><strong>Plazo:</strong> $plazo_meses meses</p>
                        <p><strong>Tipo de Pago:</strong> " . ucfirst($tipo_pago) . "</p>
                        <p><strong>Tasa de Interés:</strong> " . ($tasa_interes * 100) . "%</p>
                    </div>
                    
                    <h4>Plan de Pagos</h4>
                    <div class='tabla-cuotas-container'>
                        <table class='tabla-cuotas'>
                            <thead>
                                <tr>
                                    <th>Cuota</th>
                                    <th>Capital</th>
                                    <th>Interés</th>
                                    <th>Total Cuota</th>
                                    <th>Fecha Vencimiento</th>
                                </tr>
                            </thead>
                            <tbody>";

            // Consultar las cuotas generadas
            $stmt = $conn->prepare("SELECT numero_cuota, monto_capital, monto_interes, fecha_vencimiento 
                                  FROM cuotas 
                                  WHERE id_credito = ? 
                                  ORDER BY numero_cuota");
            $stmt->bind_param("i", $id_credito);
            $stmt->execute();
            $cuotas = $stmt->get_result();

            while ($cuota = $cuotas->fetch_assoc()) {
                $total_cuota = $cuota['monto_capital'] + $cuota['monto_interes'];
                echo "<tr>
                        <td>" . $cuota['numero_cuota'] . "</td>
                        <td>$" . number_format($cuota['monto_capital'], 2, ',', '.') . "</td>
                        <td>$" . number_format($cuota['monto_interes'], 2, ',', '.') . "</td>
                        <td>$" . number_format($total_cuota, 2, ',', '.') . "</td>
                        <td>" . date('d/m/Y', strtotime($cuota['fecha_vencimiento'])) . "</td>
                    </tr>";
            }

            echo "</tbody>
                </table>
            </div>";

            // Calcular y mostrar totales
            $total_intereses = $conn->query("SELECT SUM(monto_interes) as total FROM cuotas WHERE id_credito = $id_credito")->fetch_assoc()['total'];
            $total_a_pagar = $monto_total + $total_intereses;

            echo "<div class='resumen-totales'>
                    <p><strong>Total Intereses:</strong> $" . number_format($total_intereses, 2, ',', '.') . "</p>
                    <p><strong>Total a Pagar:</strong> $" . number_format($total_a_pagar, 2, ',', '.') . "</p>
                </div>
            </div>";
        }

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
    <!-- SECCIÓN 1: AGREGAR PRODUCTOS -->
    <div class="productos-section">
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
                        <?= $pr['nombre'] ?> (Stock: <?= $stock ?>, $<?= number_format($pr['precio'], 2, ',', '.') ?>) <?= $alerta ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <label for="cantidad">Cantidad:</label>
            <input type="number" name="cantidad" min="1" value="1" required>

            <input type="submit" name="agregar" value="Agregar">
        </form>

        <!-- SECCIÓN 2: DETALLE DE PRODUCTOS -->
        <h3>Detalle Factura</h3>
        <input type="text" id="filtroProductos" class="tabla-filtro" placeholder="Buscar en el carrito...">

        <div class="tabla-container">
            <table class="tabla-productos" id="tablaProductos">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    foreach ($_SESSION['factura'] as $id => $item):
                        $subtotal = $item['precio'] * $item['cantidad'];
                        $total += $subtotal;
                    ?>
                        <tr>
                            <td><?= $item['nombre'] ?></td>
                            <td>$<?= number_format($item['precio'], 2, ',', '.') ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="id_producto" value="<?= $id ?>">
                                    <input type="number" name="cantidad" value="<?= $item['cantidad'] ?>" min="1" max="<?= $item['stock'] ?>">
                                    <input type="submit" name="actualizar" value="Actualizar">
                                </form>
                            </td>
                            <td>$<?= number_format($subtotal, 2, ',', '.') ?></td>
                            <td>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="id_producto" value="<?= $id ?>">
                                    <input type="submit" name="eliminar" value="Eliminar">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="tabla-navegacion">
            <div class="tabla-paginacion">
                <button id="btnAnterior" disabled>&laquo; Anterior</button>
                <span id="paginaActual">Página 1</span>
                <button id="btnSiguiente">Siguiente &raquo;</button>
            </div>
            <div class="tabla-info">
                <span id="infoRegistros">Mostrando 0-0 de 0 productos</span>
            </div>
        </div>
        <h3>Total: $<?= number_format($total, 2, ',', '.') ?></h3>
    </div>

    <!-- SECCIÓN 3: CLIENTE Y MÉTODO DE PAGO -->
    <div class="cliente-pago-section">
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
                    <?php
                    // Reset the pagos result pointer
                    $pagos->data_seek(0);
                    while ($p = $pagos->fetch_assoc()):
                        $esCredito = (strtolower(trim($p['nombre'])) === 'credito');
                    ?>
                        <option value="<?= $p['num_pago'] ?>"
                            data-es-credito="<?= $esCredito ? 'true' : 'false' ?>">
                            <?= $p['nombre'] ?> (ID: <?= $p['num_pago'] ?>)
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
                <?php
                // Reset the clientes result pointer
                $clientes->data_seek(0);
                while ($c = $clientes->fetch_assoc()):
                ?>
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
    </div>
</div>

<style>
    .factura-container {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .productos-section {
        order: 1;
    }

    .cliente-pago-section {
        order: 2;
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 2px solid #ddd;
    }

    .sin-stock {
        color: red;
        font-weight: bold;
    }

    .stock-bajo {
        color: orange;
        font-weight: bold;
    }

    /* Estilos para la tabla con paginación */
    .tabla-container {
        margin-bottom: 2rem;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .tabla-filtro {
        margin-bottom: 1rem;
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1em;
    }

    .tabla-productos {
        width: 100%;
        border-collapse: collapse;
    }

    .tabla-productos th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 1;
        color: #333;
        font-weight: bold;
    }

    .tabla-productos th,
    .tabla-productos td {
        padding: 12px;
        border: 1px solid #ddd;
    }

    .tabla-productos tr:hover {
        background-color: #f5f5f5;
    }

    .tabla-navegacion {
        background: #f8f9fa;
        padding: 1rem;
        border: 1px solid #ddd;
        border-radius: 0 0 4px 4px;
        margin-top: -1px;
    }

    .tabla-paginacion {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        margin-bottom: 10px;
    }

    .tabla-paginacion button {
        padding: 8px 16px;
        border: 1px solid #0056b3;
        background: #0d6efd;
        color: white;
        cursor: pointer;
        border-radius: 4px;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .tabla-paginacion button:hover:not(:disabled) {
        background: #0056b3;
        transform: translateY(-1px);
    }

    .tabla-paginacion button:disabled {
        background: #cccccc;
        border-color: #999999;
        cursor: not-allowed;
        opacity: 0.7;
    }

    .tabla-paginacion span {
        padding: 8px 16px;
        background: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 4px;
        color: #333;
        font-weight: 500;
    }

    .tabla-info {
        text-align: center;
        color: #666;
        font-size: 0.9em;
        margin-top: 5px;
    }

    /* Estilos para el resumen de crédito */
    .resumen-container {
        margin: 2rem 0;
        padding: 2rem;
        background-color: #f8f9fa;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .resumen-container h3 {
        color: #0d6efd;
        margin-bottom: 1.5rem;
        border-bottom: 2px solid #0d6efd;
        padding-bottom: 0.5rem;
    }

    .resumen-detalles {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .resumen-detalles p {
        margin: 0.5rem 0;
        padding: 0.5rem;
        background-color: white;
        border-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .tabla-cuotas-container {
        margin: 1.5rem 0;
        overflow-x: auto;
    }

    .tabla-cuotas {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .tabla-cuotas th,
    .tabla-cuotas td {
        padding: 12px;
        border: 1px solid #dee2e6;
        text-align: center;
    }

    .tabla-cuotas th {
        background-color: #e9ecef;
        color: #495057;
        font-weight: 600;
    }

    .tabla-cuotas tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .tabla-cuotas tr:hover {
        background-color: #e9ecef;
    }

    .resumen-totales {
        margin-top: 2rem;
        padding: 1rem;
        background-color: #e9ecef;
        border-radius: 4px;
        display: flex;
        justify-content: flex-end;
        gap: 2rem;
    }

    .resumen-totales p {
        margin: 0;
        font-size: 1.1rem;
    }

    .resumen-totales p:last-child {
        color: #0d6efd;
        font-weight: bold;
    }
</style>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Script de validación
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
        }
    });

    // Búsqueda de clientes
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

    // Búsqueda de productos
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

        // Selección de producto desde sugerencia
        $(document).on('click', '.producto-sugerido', function() {
            const id = $(this).data('id');
            const nombre = $(this).data('nombre');

            $('#buscar_producto').val(nombre);
            $('#id_producto').val(id);
            $('#sugerencias_producto').fadeOut();
        });

        // Ocultar sugerencias al hacer clic fuera
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#buscar_producto, #sugerencias_producto').length) {
                $('#sugerencias_producto').fadeOut();
            }
        });
    });

    // Funcionalidad de paginación y filtrado de la tabla de productos
    $(document).ready(function() {
        const itemsPorPagina = 5;
        let paginaActual = 1;
        let filasFiltradas = [];

        function actualizarTabla() {
            const tabla = $('#tablaProductos tbody');
            const filas = tabla.find('tr');

            // Actualizar filas filtradas
            filasFiltradas = Array.from(filas).filter(fila => {
                const textoBuscado = $('#filtroProductos').val().toLowerCase();
                const contenidoFila = fila.textContent.toLowerCase();
                return contenidoFila.includes(textoBuscado);
            });

            const totalPaginas = Math.ceil(filasFiltradas.length / itemsPorPagina);

            // Asegurar que la página actual sea válida
            if (paginaActual > totalPaginas) {
                paginaActual = totalPaginas || 1;
            }

            // Ocultar todas las filas
            filas.hide();

            // Mostrar solo las filas de la página actual
            const inicio = (paginaActual - 1) * itemsPorPagina;
            const fin = inicio + itemsPorPagina;

            filasFiltradas.slice(inicio, fin).forEach(fila => {
                $(fila).show();
            });

            // Actualizar información de paginación
            $('#paginaActual').text(`Página ${paginaActual} de ${totalPaginas}`);
            $('#infoRegistros').text(`Mostrando ${inicio + 1}-${Math.min(fin, filasFiltradas.length)} de ${filasFiltradas.length} productos`);

            // Actualizar estado de los botones
            $('#btnAnterior').prop('disabled', paginaActual === 1);
            $('#btnSiguiente').prop('disabled', paginaActual >= totalPaginas);
        }

        // Manejar clic en botones de paginación
        $('#btnAnterior').click(function() {
            if (paginaActual > 1) {
                paginaActual--;
                actualizarTabla();
            }
        });

        $('#btnSiguiente').click(function() {
            const totalPaginas = Math.ceil(filasFiltradas.length / itemsPorPagina);
            if (paginaActual < totalPaginas) {
                paginaActual++;
                actualizarTabla();
            }
        });

        // Manejar filtrado
        $('#filtroProductos').on('input', function() {
            paginaActual = 1;
            actualizarTabla();
        });

        // Inicializar la tabla
        actualizarTabla();
    });
</script>