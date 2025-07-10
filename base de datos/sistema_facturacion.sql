-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 10-07-2025 a las 02:15:24
-- Versión del servidor: 8.0.40
-- Versión de PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_facturacion`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

DROP TABLE IF EXISTS `categoria`;
CREATE TABLE IF NOT EXISTS `categoria` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  PRIMARY KEY (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`id_categoria`, `nombre`, `descripcion`) VALUES
(1, 'Tecnología', 'Productos relacionados con computación, móviles y gadgets.'),
(2, 'Alimentos', 'Comestibles, bebidas y productos perecederos.'),
(3, 'Ropa', 'Vestimenta para hombre, mujer y niños.'),
(4, 'Hogar', 'Artículos para el mantenimiento y decoración del hogar.'),
(5, 'Deportes', 'Equipamiento y accesorios deportivos.'),
(6, 'Salud y Belleza', 'Productos de cuidado personal, cosmética y salud.'),
(7, 'Juguetes', 'Juguetes y juegos para todas las edades.'),
(8, 'Automotriz', 'Accesorios y repuestos para vehículos.'),
(9, 'Libros', 'Libros impresos y digitales de diversos géneros.'),
(10, 'Electrodomésticos', 'Aparatos eléctricos para uso doméstico.'),
(11, 'Bebidas Alcoholicas', 'Cerveza sabrosa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cliente`
--

DROP TABLE IF EXISTS `cliente`;
CREATE TABLE IF NOT EXISTS `cliente` (
  `id_cliente` int NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `apellido` varchar(60) NOT NULL,
  `direccion` varchar(255) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `cliente`
--

INSERT INTO `cliente` (`id_cliente`, `nombre`, `apellido`, `direccion`, `fecha_nacimiento`, `telefono`, `email`) VALUES
(1012345678, 'Carlos', 'Ramírez', 'Cra. 45 #12-34, Medellín', '1990-05-12', '3001234567', 'carlos.ramirez@correo.com'),
(1023456789, 'María', 'Gómez', 'Calle 89 #56-78, Bogotá', '1985-09-23', '3102345678', 'maria.gomez@correo.com'),
(1034567890, 'Andrés', 'Pérez', 'Av. Siempre Viva #101, Cali', '1993-03-30', '3203456789', 'andres.perez@correo.com'),
(1045678901, 'Laura', 'Martínez', 'Cl. 10 #9-40, Barranquilla', '1999-11-11', '3009876543', 'laura.martinez@correo.com'),
(1056789012, 'Jorge', 'López', 'Cra. 7 #22-33, Bucaramanga', '1980-01-17', '3108765432', 'jorge.lopez@correo.com'),
(1067890123, 'Diana', 'Torres', 'Cl. 50 #80-90, Cartagena', '1992-07-04', '3201230987', 'diana.torres@correo.com'),
(1078901234, 'Camilo', 'Moreno', 'Av. 1ra #34-21, Manizales', '1988-12-25', '3004567890', 'camilo.moreno@correo.com'),
(1089012345, 'Natalia', 'Vargas', 'Cl. 100 #25-60, Pereira', '1995-06-15', '3112345678', 'natalia.vargas@correo.com'),
(1090123456, 'Esteban', 'Mejía', 'Cra. 3 #15-90, Armenia', '1991-08-09', '3123456789', 'esteban.mejia@correo.com'),
(1101234567, 'Juliana', 'Suárez', 'Cl. 60 #30-15, Neiva', '1996-04-28', '3134567890', 'juliana.suarez@correo.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle`
--

DROP TABLE IF EXISTS `detalle`;
CREATE TABLE IF NOT EXISTS `detalle` (
  `num_detalle` int NOT NULL AUTO_INCREMENT,
  `id_factura` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio` decimal(65,0) NOT NULL,
  PRIMARY KEY (`num_detalle`),
  KEY `fk_detalle_num_factura` (`id_factura`),
  KEY `fk_detalle_producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `detalle`
--

INSERT INTO `detalle` (`num_detalle`, `id_factura`, `id_producto`, `cantidad`, `precio`) VALUES
(34, 36, 2, 4, 12000),
(35, 36, 11, 5, 2300),
(36, 36, 4, 3, 310000),
(37, 36, 3, 2, 95000),
(38, 37, 6, 2, 23000),
(39, 37, 12, 4, 4000),
(40, 38, 2, 1, 12000),
(41, 38, 11, 2, 2300),
(42, 39, 11, 4, 2300),
(43, 39, 7, 5, 18000),
(44, 40, 2, 1, 12000),
(45, 40, 8, 1, 45000),
(46, 40, 6, 13, 23000),
(47, 40, 11, 3, 2300),
(48, 41, 12, 1, 4000),
(49, 42, 12, 1, 5000),
(50, 43, 7, 12, 18000),
(51, 43, 5, 1, 85000),
(52, 47, 6, 11, 23000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factura`
--

DROP TABLE IF EXISTS `factura`;
CREATE TABLE IF NOT EXISTS `factura` (
  `num_factura` int NOT NULL AUTO_INCREMENT,
  `id_cliente` int NOT NULL,
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `num_pago` int NOT NULL,
  PRIMARY KEY (`num_factura`),
  KEY `fk_factura_num_pago` (`num_pago`),
  KEY `fk_factura_id_cliente` (`id_cliente`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `factura`
--

INSERT INTO `factura` (`num_factura`, `id_cliente`, `fecha`, `num_pago`) VALUES
(36, 1012345678, '2025-07-02 18:08:58', 1),
(37, 1056789012, '2025-07-02 18:10:21', 3),
(38, 1056789012, '2025-07-02 20:38:29', 1),
(39, 1023456789, '2025-07-02 21:28:16', 1),
(40, 1023456789, '2025-07-02 22:19:19', 2),
(41, 1023456789, '2025-07-03 07:00:39', 1),
(42, 1056789012, '2025-07-03 07:01:59', 1),
(43, 1045678901, '2025-07-03 09:01:38', 1),
(44, 1023456789, '2025-07-03 09:04:57', 1),
(45, 1012345678, '2025-07-03 09:05:09', 1),
(46, 1023456789, '2025-07-03 09:05:30', 2),
(47, 1012345678, '2025-07-03 09:05:50', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `modo_pago`
--

DROP TABLE IF EXISTS `modo_pago`;
CREATE TABLE IF NOT EXISTS `modo_pago` (
  `num_pago` int NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `otros_detalles` varchar(255) NOT NULL,
  PRIMARY KEY (`num_pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `modo_pago`
--

INSERT INTO `modo_pago` (`num_pago`, `nombre`, `otros_detalles`) VALUES
(1, 'Efectivo', 'Pago realizado en moneda local, sin intermediarios bancarios.'),
(2, 'Tarjeta de Crédito', 'Visa, MasterCard, American Express aceptadas.'),
(3, 'Tarjeta Débito', 'Transacción directa desde cuenta bancaria.'),
(4, 'Transferencia Bancaria', 'Transferencia a cuenta corriente 123456789 - Banco Colombia.'),
(5, 'Nequi', 'Pago desde cuenta Nequi asociada al número móvil.'),
(6, 'Daviplata', 'Transacción desde billetera electrónica Daviplata.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

DROP TABLE IF EXISTS `producto`;
CREATE TABLE IF NOT EXISTS `producto` (
  `id_producto` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(60) NOT NULL,
  `precio` decimal(60,0) NOT NULL,
  `stock` int NOT NULL,
  `id_categoria` int NOT NULL,
  PRIMARY KEY (`id_producto`),
  KEY `fk_producto_id_categoria` (`id_categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`id_producto`, `nombre`, `precio`, `stock`, `id_categoria`) VALUES
(1, 'Laptop HP 14\"', 2500000, 0, 1),
(2, 'Cereal Avena Crunch', 12000, 16, 2),
(3, 'Camiseta deportiva Nike', 95000, 0, 3),
(4, 'Silla ergonómica', 310000, 0, 4),
(5, 'Balón de fútbol profesional', 85000, 2, 5),
(6, 'Shampoo anticaspa 400ml', 23000, 34, 6),
(7, 'Rompecabezas 500 piezas', 18000, 0, 7),
(8, 'Limpiador de motor', 45000, 0, 8),
(9, 'Libro: Cien años de soledad', 38000, 0, 9),
(10, 'Microondas Haceb 20L', 420000, 0, 10),
(11, 'arroz', 2300, 0, 2),
(12, 'Poker', 5000, 1, 11);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

DROP TABLE IF EXISTS `usuario`;
CREATE TABLE IF NOT EXISTS `usuario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `clave` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `rol` enum('GLOBAL','ADMINISTRADOR','CAJERO') NOT NULL,
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id`, `nombre`, `correo`, `clave`, `rol`, `creado_en`) VALUES
(2, 'Kevin', 'kevinpalomino.jolianis@gmail.com', '$2y$10$arIyp5q3hUHJNWAuV67eEucK6jrFMe5qmVzeVLqL8PcO9FO7vFW8i', 'GLOBAL', '2025-06-08 16:00:19'),
(3, 'PruebaAdmin', 'admin@gmail.com', '$2y$10$LCvZsBfIbk1TUZG9iis2budUmrD3nmpO3BQP.1o1gNa4zlrjXvUHm', 'ADMINISTRADOR', '2025-06-08 21:46:15'),
(4, 'pruebaCajero', 'cajero@gmail.com', '$2y$10$Na8JZghZ0BIvviao00LrRuBsIMl7KkS3xmjZDdhEzLLbAEkTP4J/q', 'CAJERO', '2025-06-08 21:46:31');

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle`
--
ALTER TABLE `detalle`
  ADD CONSTRAINT `fk_detalle_num_factura` FOREIGN KEY (`id_factura`) REFERENCES `factura` (`num_factura`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Filtros para la tabla `factura`
--
ALTER TABLE `factura`
  ADD CONSTRAINT `fk_factura_id_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_cliente`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_factura_num_pago` FOREIGN KEY (`num_pago`) REFERENCES `modo_pago` (`num_pago`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `fk_producto_id_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
