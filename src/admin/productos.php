<?php
include "../includes/db.php";
include "../includes/auth.php";
include "../includes/header.php";
verificarRol("ADMINISTRADOR");

$accion = $_GET['accion'] ?? 'listar';
$productoEditar = null;

// 🔁 ELIMINAR producto
if ($accion === 'eliminar' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM producto WHERE id_Producto = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: productos.php");
    exit();
}

// 💾 GUARDAR nuevo o editado
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST['id_producto'] ?? null;
    $nombre = $_POST['nombre'];
    $precio = $_POST['precio'];
    $stock = $_POST['stock'];
    $id_categoria = $_POST['id_categoria'];

    if ($id) {
        // Editar
        $stmt = $conn->prepare("UPDATE producto SET nombre=?, precio=?, stock=?, id_categoria=? WHERE id_Producto=?");
        $stmt->bind_param("sdiii", $nombre, $precio, $stock, $id_categoria, $id);
    } else {
        // Nuevo
        $stmt = $conn->prepare("INSERT INTO producto (nombre, precio, stock, id_categoria) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdii", $nombre, $precio, $stock, $id_categoria);
    }

    if ($stmt->execute()) {
        echo "<p style='color:green;'>✅ Guardado correctamente.</p>";
    } else {
        echo "<p style='color:red;'>❌ Error: " . $stmt->error . "</p>";
    }

    $accion = 'listar';
}

// ✏️ EDITAR
if ($accion === 'editar' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT id_Producto AS id_producto, nombre, precio, stock, id_categoria FROM producto WHERE id_Producto = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $productoEditar = $res->fetch_assoc();
    $accion = 'formulario';
}

// ➕ NUEVO
if ($accion === 'agregar') {
    $productoEditar = ['id_producto' => '', 'nombre' => '', 'precio' => '', 'stock' => '', 'id_categoria' => ''];
    $accion = 'formulario';
}

// 🧾 Obtener lista de categorías
$categorias = [];
$res = $conn->query("SELECT * FROM categoria");
while ($cat = $res->fetch_assoc()) {
    $categorias[] = $cat;
}

// 📋 LISTAR productos
if ($accion === 'listar') {
    $query = "SELECT p.id_Producto AS id_producto, p.nombre, p.precio, p.stock, p.id_categoria, c.nombre AS categoria 
              FROM producto p 
              LEFT JOIN categoria c ON p.id_categoria = c.id_categoria";
    $result = $conn->query($query);
    echo "<h2>Gestión de Productos</h2>";
    echo "<a href='productos.php?accion=agregar'>➕ Agregar Producto</a><br><br>";
    echo "<table border='1' cellpadding='5'><tr>
            <th>ID</th><th>Nombre</th><th>Precio</th><th>Stock</th><th>Categoría</th><th>Acciones</th>
          </tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['id_producto']}</td>
                <td>" . htmlspecialchars($row['nombre']) . "</td>
                <td>$" . number_format($row['precio'], 2) . "</td>
                <td>{$row['stock']}</td>
                <td>" . htmlspecialchars($row['categoria']) . "</td>
                <td>
                    <a href='productos.php?accion=editar&id={$row['id_producto']}'>✏️ Editar</a> |
                    <a href='productos.php?accion=eliminar&id={$row['id_producto']}' onclick='return confirm(\"¿Eliminar este producto?\")'>🗑️ Eliminar</a>
                </td>
              </tr>";
    }
    echo "</table>";
}

// 📝 FORMULARIO AGREGAR/EDITAR
if ($accion === 'formulario') {
?>
    <h2><?= !empty($productoEditar['id_producto']) ? "Editar" : "Agregar" ?> Producto</h2>
    <form method="POST" action="productos.php">
        <input type="hidden" name="id_producto" value="<?= htmlspecialchars($productoEditar['id_producto']) ?>">

        <label>Nombre:</label><br>
        <input type="text" name="nombre" value="<?= htmlspecialchars($productoEditar['nombre']) ?>" required><br>

        <label>Precio:</label><br>
        <input type="number" step="0.01" name="precio" value="<?= htmlspecialchars($productoEditar['precio']) ?>" required><br>

        <label>Stock:</label><br>
        <input type="number" name="stock" value="<?= htmlspecialchars($productoEditar['stock']) ?>" required><br>

        <label>Categoría:</label><br>
        <select name="id_categoria" required>
            <option value="">Seleccione...</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id_categoria'] ?>"
                    <?= ($cat['id_categoria'] == $productoEditar['id_categoria']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">Guardar</button>
        <a href="productos.php">Cancelar</a>
    </form>
<?php
}
?>