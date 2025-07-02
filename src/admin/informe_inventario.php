<?php
include "../includes/header.php";
include "../includes/db.php";
include "../includes/auth.php";
verificarRol("ADMINISTRADOR");

$sql = "SELECT id_producto, nombre, precio, stock FROM producto ORDER BY id_producto";
$resultado = $conn->query($sql);
?>

<h2>Listado de Productos</h2>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Producto</th>
        <th>stock</th>
        <th>precio</th>
    </tr>
    <?php while ($row = $resultado->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id_producto'] ?></td>
            <td><?= $row['nombre'] ?></td>
            <td><?= $row['stock'] ?></td>
            <td>$<?= number_format($row['precio'], 0, ',', '.') ?></td>
        </tr>
    <?php endwhile; ?>
</table>

<?php include "../includes/footer.php"; ?>