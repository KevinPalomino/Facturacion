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
</head>

<body>
    <header class="main-header">
        <div class="container">
            <strong class="welcome">
                Bienvenido: <?php echo htmlspecialchars($_SESSION['correo']); ?> (<?php echo ucfirst($rol); ?>)
            </strong>
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

            <?php elseif ($rol === 'global'): ?>
                <a href="../global/usuarios.php">Gestión de Usuarios</a>
            <?php else: ?>
                <span>Rol desconocido</span>
            <?php endif; ?>
        </nav>
    </header>

    <main class="content">