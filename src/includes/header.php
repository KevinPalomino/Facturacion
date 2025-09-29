<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['correo'])) {
    header("Location: /index.php");
    exit();
}
$rol = strtolower(trim($_SESSION['rol'])); // Normaliza el rol
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Sistema de Facturación</title>
    <link rel="stylesheet" href="../css/estilos.css"> <!-- ✅ Enlace al CSS global -->
    <link rel="stylesheet" href="../css/asistente.css"> <!-- CSS del asistente -->
    <script src="../js/asistente.js" defer></script>
</head>

<body>
    <header class="main-header">
        <div class="container">
            <strong class="welcome">
                Bienvenido: <?php echo htmlspecialchars($_SESSION['correo']); ?> (<?php echo ucfirst($rol); ?>)
            </strong>
            <button id="open-assistant" class="btn assistant">Asistente</button>
            <a class="btn logout" href="../logout.php">Cerrar sesión</a>
        </div>
        <nav class="main-nav">
            <?php if ($rol === 'cajero'): ?>
                <a href="../cajero/registrar_cliente.php">Registrar Cliente</a>
                <a href="../cajero/cierre_caja.php">Cierre Caja</a>
                <a href="../cajero/nueva_factura.php">Nueva Factura</a>

            <?php elseif ($rol === 'administrador'): ?>
                <a href="../admin/informe_inventario.php">Informe Inventario</a>
                <a href="../admin/informe_ventas.php">Informe Ventas</a>
                <a href="../admin/productos.php">Productos</a>
                <a href="../admin/lista_creditos.php">Gestión de Créditos</a>

            <?php elseif ($rol === 'global'): ?>
                <a href="../global/usuarios.php">Gestión de Usuarios</a>
            <?php else: ?>
                <span>Rol desconocido</span>
            <?php endif; ?>
        </nav>
    </header>

    <!-- Chat Assistant -->
    <div id="chat-container" class="chat-container hidden">
        <div class="chat-header">
            <h3>Conta Assistant</h3>
            <button id="close-assistant" class="close-btn">&times;</button>
        </div>
        <div id="chat-messages" class="chat-messages"></div>
        <div class="chat-input-container">
            <input type="text" id="chat-input" placeholder="Escribe tu pregunta aquí...">
            <button id="send-message">Enviar</button>
        </div>
    </div>

    <main class="content">