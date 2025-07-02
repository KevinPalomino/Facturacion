<?php
session_start();
include "includes/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = $_POST['correo'];  // ✅ nombre correcto
    $clave = $_POST['clave'];

    $stmt = $conn->prepare("SELECT * FROM usuario WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $user = $resultado->fetch_assoc();

        if (password_verify($clave, $user['clave'])) {
            $_SESSION['correo'] = $user['correo'];
            $_SESSION['rol'] = $user['rol'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "Usuario no encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>

    <div class="login-container">
        <form method="POST">
            <h2>Iniciar Sesión</h2>
            <input type="email" name="correo" placeholder="Correo electrónico" required>
            <input type="password" name="clave" placeholder="Contraseña" required>
            <button type="submit">Entrar</button>
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        </form>
    </div>

</body>

</html>