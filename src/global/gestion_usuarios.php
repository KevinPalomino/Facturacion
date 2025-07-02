<?php
include "../includes/db.php";
include "../includes/auth.php";
include "../includes/header.php";
verificarRol("GLOBAL");

// Asegurar que solo usuarios con rol "global" accedan
if ($_SESSION['rol'] !== 'global') {
    header("Location: ../index.php");
    exit();
}

// Eliminar usuario si se envía la solicitud
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $conn->prepare("DELETE FROM usuario WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: gestion_usuarios.php");
    exit();
}

// Actualizar usuario si se envía el formulario de edición
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['editar_id'])) {
    $id = intval($_POST['editar_id']);
    $nombre = trim($_POST['nombre']);
    $clave = trim($_POST['clave']);
    if (!empty($clave)) {
        $clave_hashed = password_hash($clave, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuario SET nombre = ?, clave = ? WHERE id = ?");
        $stmt->bind_param("ssi", $nombre, $clave_hashed, $id);
    } else {
        $stmt = $conn->prepare("UPDATE usuario SET nombre = ? WHERE id = ?");
        $stmt->bind_param("si", $nombre, $id);
    }
    $stmt->execute();
    header("Location: gestion_usuarios.php");
    exit();
}

// Obtener todos los usuarios
$usuarios = $conn->query("SELECT * FROM usuario");
?>

<?php include "../includes/header.php"; ?>

<h2>Gestión de Usuarios</h2>

<table border="1">
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Correo</th>
        <th>Rol</th>
        <th>Acciones</th>
    </tr>
    <?php while ($row = $usuarios->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id_producto'] ?></td>
            <td><?= htmlspecialchars($row['nombre']) ?></td>
            <td><?= htmlspecialchars($row['correo']) ?></td>
            <td><?= htmlspecialchars($row['rol']) ?></td>
            <td>
                <form method="POST" style="display:inline-block;">
                    <input type="hidden" name="editar_id" value="<?= $row['id_producto'] ?>">
                    <input type="text" name="nombre" value="<?= htmlspecialchars($row['nombre']) ?>" required>
                    <input type="password" name="clave" placeholder="Nueva clave (opcional)">
                    <button type="submit">Actualizar</button>
                </form>
                <a href="?eliminar=<?= $row['id_producto'] ?>" onclick="return confirm('¿Estás seguro de eliminar este usuario?')">Eliminar</a>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

<?php include "../includes/footer.php"; ?>