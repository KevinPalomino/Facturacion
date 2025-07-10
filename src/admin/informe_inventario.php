<?php
session_start();
include "../includes/auth.php";
verificarRol("ADMINISTRADOR");
include "../includes/header.php";
include "../includes/db.php";



$sql = "SELECT id_producto, nombre, precio, stock FROM producto ORDER BY id_producto";
$resultado = $conn->query($sql);
?>

<h2>Listado de Productos</h2>

<style>
    .stock-rojo {
        color: red;
        font-weight: bold;
    }

    .stock-naranja {
        color: orange;
        font-weight: bold;
    }
</style>

<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Producto</th>
            <th>Stock</th>
            <th>Precio</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $resultado->fetch_assoc()):
            $stock = (int)$row['stock'];
            $limite_bajo = max(1, round($stock * 0.2));
            $class = '';
            $alerta = '';

            if ($stock == 0) {
                $class = 'stock-rojo';
                $alerta = '⚠ SIN STOCK';
            } elseif ($stock <= $limite_bajo) {
                $class = 'stock-naranja';
                $alerta = '⚠ STOCK BAJO';
            }
        ?>
            <tr>
                <td><?= $row['id_producto'] ?></td>
                <td><?= $row['nombre'] ?></td>
                <td class="<?= $class ?>"><?= $stock ?> <?= $alerta ?></td>
                <td>$<?= number_format($row['precio'], 0, ',', '.') ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php include "../includes/footer.php"; ?>