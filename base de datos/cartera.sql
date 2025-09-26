-- Estructura de tabla para la gestión de créditos
CREATE TABLE IF NOT EXISTS creditos (
    id_credito INT PRIMARY KEY AUTO_INCREMENT,
    id_cliente INT,
    monto_total DECIMAL(10,2),
    plazo_meses INT,
    tipo_pago ENUM('mensual', 'bimensual'),
    fecha_inicio DATE,
    estado ENUM('activo', 'cancelado', 'mora'),
    FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Estructura de tabla para las cuotas de los créditos
CREATE TABLE IF NOT EXISTS cuotas (
    id_cuota INT PRIMARY KEY AUTO_INCREMENT,
    id_credito INT,
    numero_cuota INT,
    monto_capital DECIMAL(10,2),
    monto_interes DECIMAL(10,2),
    fecha_vencimiento DATE,
    fecha_pago DATE NULL,
    estado ENUM('pendiente', 'pagado', 'vencido'),
    FOREIGN KEY (id_credito) REFERENCES creditos(id_credito)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;