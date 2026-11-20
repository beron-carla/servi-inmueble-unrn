-- ============================================================
-- Carga de datos en `serviinmueble`
-- Adaptado del dump de phpMyAdmin del 10-11-2026
-- ============================================================
-- CORRECCIONES APLICADAS:
--   1. Agregado CREATE DATABASE IF NOT EXISTS + USE serviinmueble
--   2. INSERT reordenados por dependencias (padres antes que hijos)
--   3. SET FOREIGN_KEY_CHECKS = 0 durante la carga
--   4. Bloque de LIMPIEZA para evitar choque de claves primarias
-- ============================================================
-- ADVERTENCIA: el bloque LIMPIEZA BORRA los datos actuales de las
-- tablas. Si querés conservarlos, comentá ese bloque y reemplazá
-- todos los "INSERT INTO" por "INSERT IGNORE INTO".
-- ============================================================

CREATE DATABASE IF NOT EXISTS `serviinmueble`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `serviinmueble`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

START TRANSACTION;

-- ------------------------------------------------------------
-- LIMPIEZA (destructivo) - comentar si se quiere conservar datos
-- ------------------------------------------------------------
DELETE FROM `migrations`;
DELETE FROM `historico_login`;
DELETE FROM `sesion`;
DELETE FROM `transaccion`;
DELETE FROM `factura`;
DELETE FROM `propiedad_servicio`;
DELETE FROM `modificacion_usuario`;
DELETE FROM `propiedad`;
DELETE FROM `direccion`;
DELETE FROM `tipo_servicio`;
DELETE FROM `tipo_inmueble`;
DELETE FROM `usuario`;

-- ------------------------------------------------------------
-- 1. USUARIOS (padre de propiedad, sesion y transaccion)
-- ------------------------------------------------------------
INSERT INTO `usuario` (`id_usuario`, `nombre_completo`, `mail`, `contrasenia`, `rol`, `estado`, `fecha_alta`, `cambiar_contrasenia`) VALUES
(1, 'Administrador', 'admin@serviinmueble.local', '$argon2id$v=19$m=65536,t=4,p=1$RmNnN0psdWVjZThqZVNzdg$UI7F3L9QGuzSe86WAjOd9rKPuu0+dliA5wVXEJKYc4M', 'admin', 'activo', '2026-11-08 18:39:33', 0),
(2, 'Propietario Demo', 'propietario@serviinmueble.local', '$argon2id$v=19$m=65536,t=4,p=1$SGtydVhHVmtiVEltc1JuNQ$qvRR1IdexdN4pXdwgqeD+c1MUdh/6emKg+cBuyxf8e0', 'propietario', 'activo', '2026-11-08 18:39:34', 0),
(3, 'Santiago Abdala', 'santiagoabdala270@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$R2llaDBUcjNPc0VhSmFDdw$EIavu5LjaSQ4VFGvdz0aQYUavSbqOy9g6BwLkmLt0aw', 'propietario', 'activo', '2026-11-09 22:44:59', 0);

-- ------------------------------------------------------------
-- 2. CATALOGOS Y DIRECCION
-- ------------------------------------------------------------
INSERT INTO `direccion` (`id_direccion`, `calle`, `numero`, `piso`, `depto`, `ciudad`) VALUES
(1, 'Mitre', 450, 2, 'B', 'General Roca');

INSERT INTO `tipo_inmueble` (`id_tipo_inmueble`, `nombre`) VALUES
(1, 'casa'),
(2, 'departamento'),
(3, 'local comercial'),
(4, 'oficina'),
(5, 'galpon');

INSERT INTO `tipo_servicio` (`id_tipo_servicio`, `tipo`, `categoria`, `unidad_medida`, `proveedor_predeterminado`, `activo`) VALUES
(1, 'agua', 'publico', 'm³', 'Aguas Rionegrinas', 1),
(2, 'gas', 'publico', 'm³', 'Camuzzi', 1),
(3, 'electricidad', 'publico', 'kWh', 'Edenor', 1),
(4, 'internet', 'privado', 'Gigas', 'Proveedor de internet', 1),
(5, 'telefonia', 'privado', 'Gigas', 'Proveedor telefónico', 1),
(6, 'television', 'privado', 'Gigas', 'Proveedor de televisión', 1),
(7, 'impuesto', 'publico', 'Moneda fija', 'Municipalidad', 1),
(8, 'expensas', 'privado', 'Moneda fija', 'Administración del edificio', 1),
(9, 'otro', 'privado', 'Moneda fija', 'Proveedor varios', 1),
(10, 'electricidad', 'publico', 'kWh', 'proveedor Edenor', 1);

-- ------------------------------------------------------------
-- 3. PROPIEDAD Y SERVICIOS
-- ------------------------------------------------------------
INSERT INTO `propiedad` (`id_propiedad`, `alias`, `superficie`, `estado`, `id_tipo_inmueble`, `id_propietario`, `id_direccion`) VALUES
(1, 'Depto Centro', 55.00, 'disponible', 2, 2, 1);

INSERT INTO `propiedad_servicio` (`id_propiedad_servicio`, `id_propiedad`, `id_tipo_servicio`, `codigo_pago_electronico`, `numero_cuenta_contrato`, `numero_medidor`, `domicilio_facturacion`, `titular`, `estado`) VALUES
(1, 1, 3, 'DEMO-EDENOR-01', 'CTA-DEMO-01', NULL, 'Mitre 450, 2 B', 'Propietario Demo', 'activo');

-- ------------------------------------------------------------
-- 4. FACTURAS, TRANSACCIONES E HISTORIAL
-- ------------------------------------------------------------
INSERT INTO `factura` (`id_factura`, `id_propiedad_servicio`, `periodo`, `periodo_mes`, `periodo_anio`, `fecha_emision`, `fecha_primer_vencimiento`, `fecha_segundo_vencimiento`, `monto_total`, `consumo_del_periodo`, `lectura_anterior`, `lectura_actual`, `unidad_medida`, `proveedor`, `categoria_factura`, `estado`) VALUES
(1, 1, 'mes', '09', '2026', '2026-09-01', '2026-09-10', NULL, 15000.00, 100, 1000, 1100, 'kWh', 'Edenor', 'publico', 'pagada'),
(2, 1, 'mes', '10', '2026', '2026-10-01', '2026-10-10', NULL, 15000.00, 100, 1000, 1100, 'kWh', 'Edenor', 'publico', 'pendiente'),
(3, 1, 'mes', '12', '2026', '2026-12-01', '2026-12-10', NULL, 15000.00, 100, 1000, 1100, 'kWh', 'Edenor', 'publico', 'pendiente');

INSERT INTO `transaccion` (`id_transaccion`, `id_factura`, `id_propietario`, `fecha_pago`, `monto_abonado`, `medio_pago`, `numero_comprobante`, `comprobante_digital`, `en_mora`) VALUES
(1, 1, 2, '2026-10-01', 15000.00, 'transferencia', 'DEMO-FACTURA-PAGADA', NULL, 0);

INSERT INTO `historico_login` (`id_intento`, `email`, `ip`, `exitoso`, `fecha`) VALUES
(1, 'admin@serviinmueble.local', '::1', 0, '2026-11-09 19:11:49'),
(2, 'admin@serviinmueble.local', '::1', 1, '2026-11-09 19:12:00'),
(3, 'admin@serviinmueble.local', '::1', 0, '2026-11-09 22:43:02'),
(4, 'admin@serviinmueble.local', '::1', 0, '2026-11-09 22:43:15'),
(5, 'admin@serviinmueble.local', '::1', 1, '2026-11-09 22:43:24'),
(6, 'santiagoabdala270@gmail.com', '::1', 1, '2026-11-09 22:51:19'),
(7, 'santiagoabdala270@gmail.com', '::1', 0, '2026-11-09 22:52:57'),
(8, 'santiagoabdala270@gmail.com', '::1', 0, '2026-11-09 22:53:04'),
(9, 'santiagoabdala270@gmail.com', '::1', 0, '2026-11-09 22:53:07'),
(10, 'propietario@serviinmueble.local', '::1', 0, '2026-11-09 22:55:13'),
(11, 'propietario@serviinmueble.local', '::1', 0, '2026-11-09 22:55:45'),
(12, 'propietario@serviinmueble.local', '::1', 0, '2026-11-09 22:57:26'),
(13, 'propietario@serviinmueble.local', '::1', 0, '2026-11-09 23:01:05'),
(14, 'santiagoabdala270@gmail.com', '::1', 1, '2026-11-10 12:04:50');

-- ------------------------------------------------------------
-- 5. SESIONES
-- ------------------------------------------------------------
INSERT INTO `sesion` (`id_sesion`, `id_usuario`, `token`, `fecha_creacion`, `ultima_actividad`) VALUES
(1, 1, 'f165011f9976f0484376858b7981215e3b31b862f85192380de041b0097ffd7a', '2026-11-09 19:12:00', '2026-11-09 19:12:28'),
(2, 1, 'a4685ded80949dfd9f31f55431fc56683fad2cd7c833e2ae8be4208203efde3c', '2026-11-09 22:43:24', '2026-11-09 23:10:33'),
(4, 3, 'efc997c8a5cbfd742687084e6207b5dd2915d02da0596105a2d251900196cb23', '2026-11-10 12:04:50', '2026-11-10 12:05:28');

-- ------------------------------------------------------------
-- 6. MIGRACIONES (marca el esquema como ya aplicado)
-- ------------------------------------------------------------
INSERT INTO `migrations` (`id`, `version`, `class`, `group`, `namespace`, `time`, `batch`) VALUES
(1, '2026-11-09-000001', 'App\\Database\\Migrations\\CreateTipoInmueble', 'default', 'App', 1794173972, 1),
(2, '2026-11-09-000002', 'App\\Database\\Migrations\\CreateTipoServicio', 'default', 'App', 1794173972, 1),
(3, '2026-11-09-000003', 'App\\Database\\Migrations\\CreateDireccion', 'default', 'App', 1794173972, 1),
(4, '2026-11-09-000004', 'App\\Database\\Migrations\\CreateUsuario', 'default', 'App', 1794173972, 1),
(5, '2026-11-09-000005', 'App\\Database\\Migrations\\CreateModificacionUsuario', 'default', 'App', 1794173972, 1),
(6, '2026-11-09-000006', 'App\\Database\\Migrations\\CreateSesion', 'default', 'App', 1794173972, 1),
(7, '2026-11-09-000007', 'App\\Database\\Migrations\\CreatePropiedad', 'default', 'App', 1794173972, 1),
(8, '2026-11-09-000008', 'App\\Database\\Migrations\\CreatePropiedadServicio', 'default', 'App', 1794173972, 1),
(9, '2026-11-09-000009', 'App\\Database\\Migrations\\CreateFactura', 'default', 'App', 1794173972, 1),
(10, '2026-11-09-000010', 'App\\Database\\Migrations\\CreateTransaccion', 'default', 'App', 1794173973, 1),
(11, '2026-11-09-000011', 'App\\Database\\Migrations\\CreateHistoricoLogin', 'default', 'App', 1794173973, 1),
(12, '2026-11-09-000012', 'App\\Database\\Migrations\\CreateTipoServicioTriggers', 'default', 'App', 1794173973, 1);

COMMIT;

SET FOREIGN_KEY_CHECKS = 1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
