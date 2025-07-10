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
            <select name="num_pago" required>
                <option value="">Seleccione</option>
                <?php while ($p = $pagos->fetch_assoc()): ?>
                    <option value="<?= $p['num_pago'] ?>"><?= $p['nombre'] ?></option>
                <?php endwhile; ?>
            </select>
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