<?php
// nueva_factura.php
session_start();
include "../includes/db.php";
include "../includes/auth.php";
include "../includes/header.php";
verificarRol("CAJERO");

if (!isset($_SESSION['factura'])) {
    $_SESSION['factura'] = [];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Agregar producto
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

    // Eliminar producto
    if (isset($_POST['eliminar'])) {
        $id_producto = (int)$_POST['id_producto'];
        unset($_SESSION['factura'][$id_producto]);
    }

    // Actualizar cantidad
    if (isset($_POST['actualizar'])) {
        $id_producto = (int)$_POST['id_producto'];
        $cantidad = (int)$_POST['cantidad'];

        if ($cantidad > 0 && $cantidad <= $_SESSION['factura'][$id_producto]['stock']) {
            $_SESSION['factura'][$id_producto]['cantidad'] = $cantidad;
        }
    }

    // Guardar factura
    if (isset($_POST['guardar_factura'])) {
        $id_cliente = $_POST['id_cliente'];
        $num_pago = $_POST['num_pago'];
        $productos = $_SESSION['factura'];

        // Validar stock
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

        // Insertar factura
        $stmt = $conn->prepare("INSERT INTO factura (id_cliente, fecha, num_pago) VALUES (?, NOW(), ?)");
        $stmt->bind_param("ii", $id_cliente, $num_pago);
        $stmt->execute();
        $num_factura = $stmt->insert_id;
        $stmt->close();

        foreach ($productos as $id_producto => $datos) {
            $cantidad = (int)$datos['cantidad'];
            $precio = (float)$datos['precio'];

            // Insertar detalle
            $stmt = $conn->prepare("INSERT INTO detalle (id_factura, id_producto, cantidad, precio) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $num_factura, $id_producto, $cantidad, $precio);
            $stmt->execute();
            $stmt->close();

            // Actualizar stock
            $stmt = $conn->prepare("UPDATE producto SET stock = stock - ? WHERE id_producto = ?");
            $stmt->bind_param("ii", $cantidad, $id_producto);
            $stmt->execute();
            $stmt->close();
        }

        $_SESSION['factura'] = [];
        echo "<p style='color:green'>Factura registrada correctamente.</p>";
    }
}

$clientes = $conn->query("SELECT id_cliente, nombre, apellido FROM cliente");
$pagos = $conn->query("SELECT num_pago, nombre FROM modo_pago");
$productos = $conn->query("SELECT id_producto, nombre, precio, stock FROM producto");
?>

<h2>Nueva Factura</h2>

<div class="factura-container">
    <form method="POST">

        <div style="margin-bottom: 15px;">
            <label for="buscar_cliente">Buscar cliente (ID):</label><br>
            <input type="text" id="buscar_cliente" name="buscar_cliente" placeholder="Ingrese el ID del cliente" autocomplete="off" style="width: 300px;">
            <div id="sugerencias_cliente" style="border: 1px solid #ccc; display:none; position:absolute; background:#fff; z-index:1000; width:300px;"></div>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="nombre_cliente">Nombre del cliente:</label><br>
            <input type="text" id="nombre_cliente" name="nombre_cliente" readonly style="width: 300px; background-color: #f3f3f3;">
        </div>

        <!-- Select oculto pero funcional -->
        <select name="id_cliente" id="id_cliente" style="display:none;" required>
            <?php while ($c = $clientes->fetch_assoc()): ?>
                <option value="<?= $c['id_cliente'] ?>">
                    <?= $c['nombre'] . ' ' . $c['apellido'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <div style="margin-bottom: 15px;">
            <label for="num_pago">Método de Pago:</label><br>
            <select name="num_pago" required style="width: 300px;">
                <option value="">Seleccione</option>
                <?php while ($p = $pagos->fetch_assoc()): ?>
                    <option value="<?= $p['num_pago'] ?>"><?= $p['nombre'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <input type="submit" name="guardar_factura" value="Guardar Factura">
    </form>




    <h3>Agregar Producto</h3>
    <form method="POST">
        <select name="id_producto" required>
            <option value="">Seleccione producto</option>
            <?php while ($pr = $productos->fetch_assoc()): ?>
                <option value="<?= $pr['id_producto'] ?>">
                    <?= $pr['nombre'] ?> (Stock: <?= $pr['stock'] ?>, Precio: $<?= $pr['precio'] ?>)
                </option>
            <?php endwhile; ?>
        </select>
        Cantidad: <input type="number" name="cantidad" min="1" value="1" required>
        <input type="submit" name="agregar" value="Agregar">
    </form>

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

        // Al hacer clic en una sugerencia
        // Al hacer clic en una sugerencia
        $(document).on('click', '.cliente-sugerido', function() {
            var id = $(this).data('id');
            var nombre = $(this).text();

            $('#buscar_cliente').val(id);
            $('#sugerencias_cliente').fadeOut();

            // Cambiar input de nombre visible
            $('#nombre_cliente').val(nombre);

            // Cambiar valor del <select> oculto
            $('#id_cliente').val(id);
        });
    });
</script>