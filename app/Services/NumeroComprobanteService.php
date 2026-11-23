<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;

final class NumeroComprobanteService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public function siguiente(string $fechaPago): string
    {
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaPago);
        $errores = DateTimeImmutable::getLastErrors();

        if ($fecha === false
            || ($errores !== false
                && ($errores['warning_count'] !== 0 || $errores['error_count'] !== 0))
            || $fecha->format('Y-m-d') !== $fechaPago
        ) {
            throw new InvalidArgumentException('La fecha de pago no es válida.');
        }

        if ($this->db->query(
            'INSERT IGNORE INTO `secuencia_comprobante` (`fecha`, `ultimo`) VALUES (?, 0)',
            [$fechaPago],
        ) === false) {
            throw new RuntimeException('No se pudo inicializar la secuencia del comprobante.');
        }
        $resultado = $this->db->query(
            'SELECT `ultimo` FROM `secuencia_comprobante` WHERE `fecha` = ? FOR UPDATE',
            [$fechaPago],
        );
        if ($resultado === false) {
            throw new RuntimeException('No se pudo consultar la secuencia del comprobante.');
        }
        $fila = $resultado->getRowArray();

        if ($fila === null) {
            throw new RuntimeException('No se pudo obtener la secuencia del comprobante.');
        }

        $ultimo = (int) $fila['ultimo'];
        if ($ultimo >= 999999) {
            throw new RuntimeException('Se alcanzó el límite diario de números de comprobante.');
        }

        $siguiente = $ultimo + 1;
        if ($this->db->query(
            'UPDATE `secuencia_comprobante` SET `ultimo` = ? WHERE `fecha` = ?',
            [$siguiente, $fechaPago],
        ) === false) {
            throw new RuntimeException('No se pudo actualizar la secuencia del comprobante.');
        }

        return sprintf('CP-%s-%06d', $fecha->format('Ymd'), $siguiente);
    }
}
