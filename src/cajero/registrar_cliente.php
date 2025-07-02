<?php
include "../includes/db.php";
include "../includes/auth.php";
include "../includes/header.php";
verificarRol("CAJERO");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $apellido = $_POST["apellido"];
    $direccion = $_POST["direccion"];
    $fecha_nacimiento = $_POST["fecha_nacimiento"];
    $telefono = $_POST["telefono"];
    $email = $_POST["email"];

    // Calcular edad
    $fecha_nacimiento_dt = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nacimiento_dt)->y;

    if ($edad < 18) {
        echo "❌ El cliente debe tener al menos 18 años para ser registrado.";
    } else {
        $stmt = $conn->prepare("INSERT INTO cliente (nombre, apellido, direccion, fecha_nacimiento, telefono, email) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $nombre, $apellido, $direccion, $fecha_nacimiento, $telefono, $email);

        if ($stmt->execute()) {
            echo "✅ Cliente registrado exitosamente. <a href='registrar_cliente.php'>Registrar otro</a>";
        } else {
            echo "❌ Error al registrar cliente: " . $stmt->error;
        }

        $stmt->close();
    }

    $conn->close();
}
?>

<h2>Registrar Cliente</h2>
<form method="POST">
    <label>Nombre:</label><br>
    <input type="text" name="nombre" required><br>

    <label>Apellido:</label><br>
    <input type="text" name="apellido" required><br>

    <label>Dirección:</label><br>
    <input type="text" name="direccion" required><br>

    <label>Fecha de Nacimiento:</label><br>
    <input type="date" name="fecha_nacimiento" required><br>

    <label>Teléfono:</label><br>
    <input type="text" name="telefono" required><br>

    <label>Correo electrónico:</label><br>
    <input type="email" name="email" required><br><br>

    <button type="submit">Registrar Cliente</button>
</form>