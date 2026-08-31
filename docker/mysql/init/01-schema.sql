SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS productos (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT NULL,
    precio DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO productos (id, nombre, descripcion, precio) VALUES
    (1, 'Teclado mecánico', 'Switches azules y conexión USB-C.', 185000.00),
    (2, 'Monitor 27 pulgadas', 'Panel IPS con resolución QHD.', 742500.50),
    (3, 'Mouse inalámbrico', 'Sensor óptico y batería recargable.', 94990.00),
    (4, 'Notebook 14 pulgadas', '16 GB de RAM y SSD de 512 GB.', 1899999.99),
    (5, 'Auriculares Bluetooth', 'Cancelación de ruido y estuche de carga.', 268400.75);
