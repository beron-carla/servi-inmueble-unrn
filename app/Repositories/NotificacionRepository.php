<?php

namespace App\Repositories;

use App\Models\NotificacionModel;
use CodeIgniter\Database\BaseConnection;

class NotificacionRepository
{
    private const POR_PAGINA = 20;

    private BaseConnection $db;

    public function __construct(
        private ?NotificacionModel $model = null,
        ?BaseConnection $db = null,
    ) {
        $this->db = $db ?? db_connect();
        $this->model ??= new NotificacionModel();
    }

    public function insertarIdempotente(array $datos): bool
    {
        $sql = 'INSERT INTO notificacion '
            . '(id_usuario, id_factura, id_transaccion, tipo, mensaje, leida, fecha_creacion, idempotencia_key) '
            . 'VALUES (?, ?, ?, ?, ?, 0, NOW(), ?) '
            . 'ON DUPLICATE KEY UPDATE id_notificacion = id_notificacion';

        $resultado = $this->db->query($sql, [
            (int) $datos['id_usuario'],
            (int) $datos['id_factura'],
            $datos['id_transaccion'] !== null ? (int) $datos['id_transaccion'] : null,
            (string) $datos['tipo'],
            (string) $datos['mensaje'],
            (string) $datos['idempotencia_key'],
        ]);

        return $resultado && $this->db->affectedRows() === 1;
    }

    public function facturasPendientesConVencimiento(string $hoy): array
    {
        return $this->db->table('factura')
            ->select([
                'factura.id_factura',
                'factura.estado',
                'factura.fecha_primer_vencimiento',
                'factura.fecha_segundo_vencimiento',
                'factura.periodo',
                'factura.periodo_mes',
                'factura.periodo_anio',
                'factura.monto_total',
                'propiedad.id_propietario',
            ])
            ->select('COALESCE(factura.fecha_segundo_vencimiento, factura.fecha_primer_vencimiento) AS vencimiento_aplicable', false)
            ->join('propiedad_servicio', 'propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio')
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad')
            ->where('factura.estado', 'pendiente')
            ->get()
            ->getResultArray();
    }

    public function buscarPorId(int $idNotificacion): ?array
    {
        return $this->model->find($idNotificacion) ?: null;
    }

    public function listar(array $filtros, ?int $idPropietario): array
    {
        $consulta = $this->consultaListado($filtros, $idPropietario);
        $total = $consulta->countAllResults();
        $paginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min(max(1, (int) ($filtros['page'] ?? 1)), $paginas);

        $consulta = $this->consultaListado($filtros, $idPropietario)
            ->select([
                'notificacion.*',
                'factura.periodo',
                'factura.periodo_mes',
                'factura.periodo_anio',
                'transaccion.estado AS estado_pago',
                'transaccion.motivo_rechazo',
            ])
            ->orderBy('notificacion.fecha_creacion', 'DESC')
            ->orderBy('notificacion.id_notificacion', 'DESC');

        $notificaciones = $consulta
            ->get(self::POR_PAGINA, ($pagina - 1) * self::POR_PAGINA)
            ->getResultArray();

        return [
            'notificaciones' => $notificaciones,
            'pagina' => $pagina,
            'paginas' => $paginas,
            'total' => $total,
            'por_pagina' => self::POR_PAGINA,
        ];
    }

    public function contarNoLeidas(int $idUsuario): int
    {
        return (int) $this->db->table('notificacion')
            ->where('id_usuario', $idUsuario)
            ->where('leida', 0)
            ->countAllResults();
    }

    public function marcarLeida(int $idNotificacion, int $idUsuario): bool
    {
        return $this->db->table('notificacion')
            ->where('id_notificacion', $idNotificacion)
            ->where('id_usuario', $idUsuario)
            ->update(['leida' => 1]);
    }

    public function marcarTodasLeidas(int $idUsuario): int
    {
        $this->db->table('notificacion')
            ->where('id_usuario', $idUsuario)
            ->where('leida', 0)
            ->update(['leida' => 1]);

        return (int) $this->db->affectedRows();
    }

    private function consultaListado(array $filtros, ?int $idPropietario)
    {
        $consulta = $this->db->table('notificacion')
            ->join('factura', 'factura.id_factura = notificacion.id_factura')
            ->join('transaccion', 'transaccion.id_transaccion = notificacion.id_transaccion', 'left');

        if ($idPropietario !== null) {
            $consulta->where('notificacion.id_usuario', $idPropietario);
        }
        if (($filtros['tipo'] ?? '') !== '') {
            $consulta->where('notificacion.tipo', $filtros['tipo']);
        }
        if (($filtros['leida'] ?? '') !== '') {
            $consulta->where('notificacion.leida', (int) $filtros['leida']);
        }

        return $consulta;
    }
}
