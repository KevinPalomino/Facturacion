<?php
include "../includes/db.php";
include "../includes/auth.php";
include "../includes/header.php";
verificarRol("GLOBAL");

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $clave = $_POST['clave'];
    $rol = $_POST['rol'];

    // Validar campos
    if (empty($nombre) || empty($correo) || empty($clave) || empty($rol)) {
        $mensaje = "Todos los campos son obligatorios.";
    } else {
        // Hashear la clave
        $claveHasheada = password_hash($clave, PASSWORD_DEFAULT);

        // Insertar usuario
        $stmt = $conn->prepare("INSERT INTO usuario (nombre, correo, clave, rol) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre, $correo, $claveHasheada, $rol);

        if ($stmt->execute()) {
            $mensaje = "Usuario creado exitosamente.";
        } else {
            $mensaje = "Error al crear usuario.";
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Crear Usuario</title>
    <meta charset="utf-8">
</head>

<body>
    <h2>Crear Nuevo Usuario</h2>

    <?php if (isset($mensaje)) echo "<p style='color:blue;'>$mensaje</p>"; ?>

    <form method="POST">
        <label>Nombre:</label><br>
        <input type="text" name="nombre" required><br><br>

        <label>Correo:</label><br>
        <input type="email" name="correo" required><br><br>

        <label>Contraseña:</label><br>
        <input type="password" name="clave" required><br><br>

        <label>Rol:</label><br>
        <select name="rol" required>
            <option value="global">Global</option>
            <option value="administrador">Administrador</option>
            <option value="cajero">Cajero</option>
        </select>


        <input type="submit" value="Crear Usuario">
    </form>

    <a href="../logout.php" style="float: right; margin: 10px;">Cerrar Sesión</a>
</body>

</html>