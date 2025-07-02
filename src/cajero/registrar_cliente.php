<?php
include "../includes/db.php";
include "../includes/auth.php";
include "../includes/header.php";
verificarRol("CAJERO");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_cliente = $_POST["id_cliente"]; // Cedula
    $nombre = $_POST["nombre"];
    $apellido = $_POST["apellido"];
    $direccion = $_POST["direccion"];
    $fecha_nacimiento = $_POST["fecha_nacimiento"];
    $telefono = $_POST["telefono"];
    $email = $_POST["email"];

    // Validar si es número
    if (!ctype_digit($id_cliente)) {
        echo "❌ La cédula debe contener solo números.";
        exit;
    }

    // Calcular edad
    $fecha_nacimiento_dt = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    $edad = $hoy->diff($fecha_nacimiento_dt)->y;

    if ($edad < 18) {
        echo "❌ El cliente debe tener al menos 18 años para ser registrado.";
    } else {
        // Verificar si la cédula ya existe
        $verificar = $conn->prepare("SELECT 1 FROM cliente WHERE id_cliente = ?");
        $verificar->bind_param("i", $id_cliente);
        $verificar->execute();
        $verificar->store_result();

        if ($verificar->num_rows > 0) {
            echo "❌ Ya existe un cliente registrado con esta cédula.";
        } else {
            $stmt = $conn->prepare("INSERT INTO cliente (id_cliente, nombre, apellido, direccion, fecha_nacimiento, telefono, email) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $id_cliente, $nombre, $apellido, $direccion, $fecha_nacimiento, $telefono, $email);

            if ($stmt->execute()) {
                echo "✅ Cliente registrado exitosamente. <a href='registrar_cliente.php'>Registrar otro</a>";
            } else {
                echo "❌ Error al registrar cliente: " . $stmt->error;
            }

            $stmt->close();
        }

        $verificar->close();
    }

    $conn->close();
}
?>

<h2>Registrar Cliente</h2>
<form method="POST">
    <label>Cédula:</label><br>
    <input type="text" name="id_cliente" required pattern="\d{6,}" title="Debe ser un número de al menos 6 dígitos"><br>

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