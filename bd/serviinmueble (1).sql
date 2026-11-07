-- ============================================================
-- Base de datos: serviinmueble  (compatible con MariaDB)
-- Esquema actualizado según HU-01 a HU-15 (Hito 1)
-- ============================================================
-- Cambios aplicados:
--  * usuario.estado ENUM('activo','bloqueado','baja')
--    (reemplaza al booleano usuario.bloqueado)
--  * propiedad.estado incluye 'inactivo' (baja lógica)
--  * propiedad_servicio.estado incluye 'inactivo' y admite NULL
--  * propiedad_servicio.numero_medidor admite NULL (medidor opcional)
--  * tipo_servicio.nombre pasa a llamarse tipo y usa ENUM de servicios
--  * tipo_servicio.categoria ENUM('publico','privado')
--  * tipo_servicio.unidad_medida ENUM + trigger de compatibilidad
--  * factura.categoria pasa a categoria_factura ENUM('publico','privado')
--  * factura.periodo pasa a ENUM('mes','anio') + periodo_mes/anio
--  * propiedad.superficie DECIMAL(10,2)
--  * direccion.provincia eliminada
--  * historico_login.exitoso para poder contar intentos fallidos
--  * transaccion.numero_comprobante alfanumérico (VARCHAR)
-- ============================================================

SET NAMES utf8mb4;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `serviinmueble`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `serviinmueble`;

SET FOREIGN_KEY_CHECKS = 0;

-- Se elimina también la tabla con su nombre anterior (intento_login)
DROP TABLE IF EXISTS `intento_login`;
DROP TABLE IF EXISTS `historico_login`;
DROP TABLE IF EXISTS `transaccion`;
DROP TABLE IF EXISTS `factura`;
DROP TABLE IF EXISTS `propiedad_servicio`;
DROP TABLE IF EXISTS `propiedad`;
DROP TABLE IF EXISTS `sesion`;
DROP TABLE IF EXISTS `modificacion_usuario`;
DROP TABLE IF EXISTS `usuario`;
DROP TABLE IF EXISTS `direccion`;
DROP TABLE IF EXISTS `tipo_servicio`;
DROP TABLE IF EXISTS `tipo_inmueble`;

-- ------------------------------------------------------------
-- tipo_inmueble
-- Solo los tipos definidos en HU-10 (CA-10.2)
-- ------------------------------------------------------------
CREATE TABLE `tipo_inmueble` (
  `id_tipo_inmueble` int NOT NULL AUTO_INCREMENT,
  `nombre` ENUM('casa','departamento','local comercial','oficina','galpon') NOT NULL,
  PRIMARY KEY (`id_tipo_inmueble`),
  UNIQUE KEY `index_nombre_tipo_inmueble` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- tipo_servicio
-- tipo: nombre del servicio (agua, gas, electricidad, etc.)
-- categoria: público / privado (HU-07 CA-07.2)
-- unidad_medida: catálogo fijo del Hito 1 (HU-07 CA-07.3)
-- ------------------------------------------------------------
CREATE TABLE `tipo_servicio` (
  `id_tipo_servicio` int NOT NULL AUTO_INCREMENT,
  `tipo` ENUM('agua','gas','electricidad','internet','telefonia','television','impuesto','expensas','otro') NOT NULL,
  `categoria` ENUM('publico','privado') NOT NULL,
  `unidad_medida` ENUM('kWh','m³','Gigas','Moneda fija') NOT NULL,
  `proveedor_predeterminado` varchar(45) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 cuando es bloqueado/dado de baja',
  PRIMARY KEY (`id_tipo_servicio`),
  UNIQUE KEY `index_tipo_proveedor` (`tipo`,`proveedor_predeterminado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- direccion
-- HU-10 no exige provincia, por eso se elimina.
-- ------------------------------------------------------------
CREATE TABLE `direccion` (
  `id_direccion` int NOT NULL AUTO_INCREMENT,
  `calle` varchar(45) NOT NULL,
  `numero` int NOT NULL,
  `piso` int DEFAULT NULL,
  `depto` varchar(10) DEFAULT NULL,
  `ciudad` varchar(60) NOT NULL,
  PRIMARY KEY (`id_direccion`),
  KEY `index_calle_nro` (`calle`,`numero`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- usuario
-- estado: Activo / Bloqueado / Baja (HU-05 CA-05.2)
-- La baja es lógica: el usuario nunca se borra físicamente.
-- ------------------------------------------------------------
CREATE TABLE `usuario` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(50) NOT NULL,
  `mail` varchar(100) NOT NULL,
  `contrasenia` varchar(255) NOT NULL COMMENT 'hash (password_hash), nunca texto plano',
  `rol` ENUM('propietario','admin') NOT NULL DEFAULT 'propietario' COMMENT 'por defecto es propietario, a menos que un admin cree a otro admin',
  `estado` ENUM('activo','bloqueado','baja') NOT NULL DEFAULT 'activo' COMMENT 'Baja = baja lógica; Bloqueado = bloqueo administrativo',
  `fecha_alta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cambiar_contrasenia` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = debe cambiar la contrasenia (restauracion o temporal creada por admin)',
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `index_mail` (`mail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- modificacion_usuario
-- Registra qué admin modificó a qué usuario y cuándo (CA-05.6)
-- ------------------------------------------------------------
CREATE TABLE `modificacion_usuario` (
  `id_modificacion` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL COMMENT 'usuario cuyos datos fueron modificados',
  `id_admin` int NOT NULL COMMENT 'usuario admin que realizo la modificacion',
  `fecha_modificacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_modificacion`),
  KEY `index_modificacion_usuario` (`id_usuario`,`fecha_modificacion`),
  KEY `index_modificacion_admin` (`id_admin`),
  CONSTRAINT `fk_modificacion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_modificacion_admin` FOREIGN KEY (`id_admin`) REFERENCES `usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- sesion
-- ------------------------------------------------------------
CREATE TABLE `sesion` (
  `id_sesion` int NOT NULL AUTO_INCREMENT,
  `id_usuario` int NOT NULL,
  `token` char(64) NOT NULL COMMENT 'identificador de sesion (por ejemplo hash SHA-256 en hexadecimal)',
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ultima_actividad` datetime DEFAULT NULL,
  PRIMARY KEY (`id_sesion`),
  UNIQUE KEY `index_token_sesion` (`token`),
  KEY `fk_sesion_usuario` (`id_usuario`),
  CONSTRAINT `fk_sesion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- propiedad
-- estado incluye 'inactivo' para la baja lógica (HU-11 CA-11.2).
-- Los inmuebles inactivos no se muestran por defecto en el frontend.
-- superficie DECIMAL para admitir hasta 2 decimales (HU-10 CA-10.4).
-- ------------------------------------------------------------
CREATE TABLE `propiedad` (
  `id_propiedad` int NOT NULL AUTO_INCREMENT,
  `alias` varchar(45) NOT NULL,
  `superficie` decimal(10,2) NOT NULL,
  `estado` ENUM('ocupado','disponible','en mantenimiento','inactivo') NOT NULL COMMENT 'inactivo = baja lógica; no se muestra en el frontend',
  `id_tipo_inmueble` int NOT NULL,
  `id_propietario` int NOT NULL,
  `id_direccion` int NOT NULL,
  PRIMARY KEY (`id_propiedad`),
  UNIQUE KEY `index_alias_propietario` (`id_propietario`,`alias`),
  KEY `fk_tipo_inmueble` (`id_tipo_inmueble`),
  KEY `fk_direccion` (`id_direccion`),
  CONSTRAINT `fk_duenio_inmueble` FOREIGN KEY (`id_propietario`) REFERENCES `usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_tipo_inmueble` FOREIGN KEY (`id_tipo_inmueble`) REFERENCES `tipo_inmueble` (`id_tipo_inmueble`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_direccion` FOREIGN KEY (`id_direccion`) REFERENCES `direccion` (`id_direccion`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- propiedad_servicio
-- estado: Activo / Pausado / En Revisión / Inactivo (HU-13 CA-13.1)
--   - 'inactivo' representa la baja lógica.
--   - Se permite NULL según la corrección solicitada.
-- numero_medidor es opcional (HU-13 CA-13.2 / H-30).
-- La unicidad de vínculo vigente por (inmueble, tipo) se resuelve
-- en la aplicación (HU-13 CA-13.4).
-- ------------------------------------------------------------
CREATE TABLE `propiedad_servicio` (
  `id_propiedad_servicio` int NOT NULL AUTO_INCREMENT,
  `id_propiedad` int NOT NULL,
  `id_tipo_servicio` int NOT NULL,
  `codigo_pago_electronico` varchar(20) NOT NULL,
  `numero_cuenta_contrato` varchar(20) NOT NULL,
  `numero_medidor` varchar(20) DEFAULT NULL COMMENT 'opcional: no todos los servicios tienen medidor',
  `domicilio_facturacion` varchar(45) NOT NULL,
  `titular` varchar(50) NOT NULL,
  `estado` ENUM('activo','pausado','en revision','inactivo') DEFAULT 'activo' COMMENT 'inactivo = baja lógica; NULL permitido',
  PRIMARY KEY (`id_propiedad_servicio`),
  KEY `fk_propiedad_servicio` (`id_propiedad`),
  KEY `fk_servicio_propiedad` (`id_tipo_servicio`),
  CONSTRAINT `fk_propiedad_servicio` FOREIGN KEY (`id_propiedad`) REFERENCES `propiedad` (`id_propiedad`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_servicio_propiedad` FOREIGN KEY (`id_tipo_servicio`) REFERENCES `tipo_servicio` (`id_tipo_servicio`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- factura
-- Registro histórico: proveedor, categoria_factura y unidad_medida
-- se guardan como "foto" del momento de emisión (H-27).
--
-- Período:
--   periodo      = 'mes' | 'anio'
--   periodo_mes  = '01'..'12' para período mensual | '00' para período anual
--   periodo_anio = año del período
-- El CHECK garantiza la coherencia entre periodo y periodo_mes.
-- ------------------------------------------------------------
CREATE TABLE `factura` (
  `id_factura` int NOT NULL AUTO_INCREMENT,
  `id_propiedad_servicio` int NOT NULL,
  `periodo` ENUM('mes','anio') NOT NULL DEFAULT 'mes' COMMENT 'granularidad del período',
  `periodo_mes` ENUM('00','01','02','03','04','05','06','07','08','09','10','11','12') NOT NULL DEFAULT '00' COMMENT '00 = período anual',
  `periodo_anio` year NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_primer_vencimiento` date NOT NULL,
  `fecha_segundo_vencimiento` date DEFAULT NULL,
  `monto_total` decimal(12,2) NOT NULL,
  `consumo_del_periodo` int NOT NULL,
  `lectura_anterior` int DEFAULT NULL,
  `lectura_actual` int DEFAULT NULL,
  `unidad_medida` varchar(15) NOT NULL COMMENT 'valor historico al momento de la factura',
  `proveedor` varchar(45) NOT NULL COMMENT 'valor historico al momento de la factura',
  `categoria_factura` ENUM('publico','privado') NOT NULL COMMENT 'valor historico al momento de la factura',
  `estado` ENUM('pendiente','pagada') NOT NULL DEFAULT 'pendiente',
  PRIMARY KEY (`id_factura`),
  UNIQUE KEY `index_factura_periodo` (`id_propiedad_servicio`,`periodo`,`periodo_anio`,`periodo_mes`),
  CONSTRAINT `chk_factura_periodo` CHECK (
    (`periodo` = 'mes' AND `periodo_mes` <> '00') OR
    (`periodo` = 'anio' AND `periodo_mes` = '00')
  ),
  CONSTRAINT `fk_factura_propiedad_servicio` FOREIGN KEY (`id_propiedad_servicio`) REFERENCES `propiedad_servicio` (`id_propiedad_servicio`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- transaccion
-- Los comprobantes de factura se consultan en esta tabla
-- (transaccion.comprobante_digital).
-- numero_comprobante es alfanumérico.
-- en_mora: 0 = pago a tiempo, 1 = pago posterior al vencimiento
-- ------------------------------------------------------------
CREATE TABLE `transaccion` (
  `id_transaccion` int NOT NULL AUTO_INCREMENT,
  `id_factura` int NOT NULL,
  `id_propietario` int NOT NULL,
  `fecha_pago` date NOT NULL,
  `monto_abonado` decimal(12,2) NOT NULL,
  `medio_pago` ENUM('transferencia','debito automatico','efectivo','billetera virtual') NOT NULL,
  `numero_comprobante` varchar(50) NOT NULL COMMENT 'código/número de comprobante alfanumérico',
  `comprobante_digital` mediumblob DEFAULT NULL COMMENT 'archivo del comprobante (opcional)',
  `en_mora` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = pago a tiempo, 1 = pago despues del vencimiento',
  PRIMARY KEY (`id_transaccion`),
  KEY `fk_transaccion_propietario` (`id_propietario`),
  KEY `fk_transaccion_factura` (`id_factura`),
  CONSTRAINT `fk_transaccion_factura` FOREIGN KEY (`id_factura`) REFERENCES `factura` (`id_factura`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_transaccion_propietario` FOREIGN KEY (`id_propietario`) REFERENCES `usuario` (`id_usuario`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- historico_login  (antes intento_login)
-- exitoso = 0 permite contar únicamente los intentos fallidos
-- para la regla de fuerza bruta de HU-02.
-- ------------------------------------------------------------
CREATE TABLE `historico_login` (
  `id_intento` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `ip` varchar(45) NOT NULL COMMENT 'soporta IPv4 e IPv6',
  `exitoso` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0 = intento fallido, 1 = login exitoso',
  `fecha` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_intento`),
  KEY `index_login_email_fecha` (`email`,`fecha`),
  KEY `index_login_ip_fecha` (`ip`,`fecha`),
  KEY `index_login_email_ip_fecha` (`email`,`ip`,`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Trigger de compatibilidad unidad_medida / tipo de servicio
-- (HU-07 CA-07.3 y corrección #11)
-- ============================================================
DELIMITER $$

DROP TRIGGER IF EXISTS `trg_tipo_servicio_unidad_bi`$$
CREATE TRIGGER `trg_tipo_servicio_unidad_bi`
BEFORE INSERT ON `tipo_servicio`
FOR EACH ROW
BEGIN
  IF NOT (
       (NEW.`tipo` = 'electricidad' AND NEW.`unidad_medida` IN ('kWh','Moneda fija'))
    OR (NEW.`tipo` = 'agua'         AND NEW.`unidad_medida` IN ('m³','Moneda fija'))
    OR (NEW.`tipo` = 'gas'          AND NEW.`unidad_medida` IN ('m³','Moneda fija'))
    OR (NEW.`tipo` = 'internet'     AND NEW.`unidad_medida` IN ('Gigas','Moneda fija'))
    OR (NEW.`tipo` = 'telefonia'    AND NEW.`unidad_medida` IN ('Gigas','Moneda fija'))
    OR (NEW.`tipo` = 'television'   AND NEW.`unidad_medida` IN ('Gigas','Moneda fija'))
    OR (NEW.`tipo` = 'impuesto'     AND NEW.`unidad_medida` = 'Moneda fija')
    OR (NEW.`tipo` = 'expensas'     AND NEW.`unidad_medida` = 'Moneda fija')
    OR (NEW.`tipo` = 'otro'         AND NEW.`unidad_medida` IN ('kWh','m³','Gigas','Moneda fija'))
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Unidad de medida no compatible con el tipo de servicio';
  END IF;
END$$

DROP TRIGGER IF EXISTS `trg_tipo_servicio_unidad_bu`$$
CREATE TRIGGER `trg_tipo_servicio_unidad_bu`
BEFORE UPDATE ON `tipo_servicio`
FOR EACH ROW
BEGIN
  IF NOT (
       (NEW.`tipo` = 'electricidad' AND NEW.`unidad_medida` IN ('kWh','Moneda fija'))
    OR (NEW.`tipo` = 'agua'         AND NEW.`unidad_medida` IN ('m³','Moneda fija'))
    OR (NEW.`tipo` = 'gas'          AND NEW.`unidad_medida` IN ('m³','Moneda fija'))
    OR (NEW.`tipo` = 'internet'     AND NEW.`unidad_medida` IN ('Gigas','Moneda fija'))
    OR (NEW.`tipo` = 'telefonia'    AND NEW.`unidad_medida` IN ('Gigas','Moneda fija'))
    OR (NEW.`tipo` = 'television'   AND NEW.`unidad_medida` IN ('Gigas','Moneda fija'))
    OR (NEW.`tipo` = 'impuesto'     AND NEW.`unidad_medida` = 'Moneda fija')
    OR (NEW.`tipo` = 'expensas'     AND NEW.`unidad_medida` = 'Moneda fija')
    OR (NEW.`tipo` = 'otro'         AND NEW.`unidad_medida` IN ('kWh','m³','Gigas','Moneda fija'))
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Unidad de medida no compatible con el tipo de servicio';
  END IF;
END$$

DELIMITER ;
