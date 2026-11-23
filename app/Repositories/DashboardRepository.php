<?php

namespace App\Repositories;

use App\Services\FacturaService;
use CodeIgniter\Database\BaseConnection;

final class DashboardRepository
{
    private BaseConnection $db;
    private FacturaService $facturas;

    public function __construct(
        ?BaseConnection $db = null,
        ?FacturaService $facturas = null,
    ) {
        $this->db = $db ?? db_connect();
        $this->facturas = $facturas ?? new FacturaService();
    }

    /**
     * @return array{total: int, admin: int, propietario: int}
     */
    public function usuariosActivos(): array
    {
        $filas = $this->db->table('usuario')
            ->select('rol, COUNT(*) AS cantidad', false)
            ->where('estado', 'activo')
            ->groupBy('rol')
            ->get()
            ->getResultArray();

        $resultado = ['total' => 0, 'admin' => 0, 'propietario' => 0];
        foreach ($filas as $fila) {
            $cantidad = (int) $fila['cantidad'];
            $resultado['total'] += $cantidad;
            if (isset($resultado[$fila['rol']])) {
                $resultado[$fila['rol']] = $cantidad;
            }
        }

        return $resultado;
    }

    public function inmueblesActivos(?int $idPropietario): int
    {
        $consulta = $this->db->table('propiedad')
            ->where('estado !=', 'inactivo');
        if ($idPropietario !== null) {
            $consulta->where('id_propietario', $idPropietario);
        }

        return (int) $consulta->countAllResults();
    }

    /**
     * @return array<string, array{cantidad: int, monto: float}>
     */
    public function facturasPorEstadoCalculado(?int $idPropietario, int $anio): array
    {
        $resultado = [
            'pendiente' => ['cantidad' => 0, 'monto' => 0.0],
            'vencida' => ['cantidad' => 0, 'monto' => 0.0],
            'pagada' => ['cantidad' => 0, 'monto' => 0.0],
        ];

        $consulta = $this->db->table('factura')
            ->select([
                'factura.estado',
                'factura.fecha_primer_vencimiento',
                'factura.fecha_segundo_vencimiento',
                'factura.monto_total',
            ])
            ->join('propiedad_servicio', 'propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio')
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad')
            ->where('factura.periodo_anio', $anio);

        if ($idPropietario !== null) {
            $consulta->where('propiedad.id_propietario', $idPropietario);
        }

        foreach ($consulta->get()->getResultArray() as $factura) {
            $estado = $this->facturas->estadoCalculado($factura);
            $resultado[$estado]['cantidad']++;
            $resultado[$estado]['monto'] += (float) $factura['monto_total'];
        }

        return $resultado;
    }

    public function pagosPendientes(?int $idPropietario): int
    {
        $consulta = $this->db->table('transaccion')
            ->join('factura', 'factura.id_factura = transaccion.id_factura')
            ->join('propiedad_servicio', 'propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio')
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad')
            ->where('transaccion.estado', 'pendiente');
        if ($idPropietario !== null) {
            $consulta->where('propiedad.id_propietario', $idPropietario);
        }

        return (int) $consulta->countAllResults();
    }

    public function recaudacionMes(?int $idPropietario, string $desde, string $hasta): float
    {
        $consulta = $this->db->table('transaccion')
            ->selectSum('monto_abonado', 'total')
            ->join('factura', 'factura.id_factura = transaccion.id_factura')
            ->join('propiedad_servicio', 'propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio')
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad')
            ->where('transaccion.estado', 'validada')
            ->where('transaccion.fecha_pago >=', $desde)
            ->where('transaccion.fecha_pago <=', $hasta);
        if ($idPropietario !== null) {
            $consulta->where('propiedad.id_propietario', $idPropietario);
        }

        $fila = $consulta->get()->getRowArray();
        return (float) ($fila['total'] ?? 0);
    }

    /**
     * @return list<array{mes: string, emitido: float, cobrado: float}>
     */
    public function serieMensual(?int $idPropietario, string $desde, string $hasta): array
    {
        $emitido = $this->db->table('factura')
            ->select("DATE_FORMAT(factura.fecha_emision, '%Y-%m') AS mes", false)
            ->selectSum('factura.monto_total', 'total')
            ->join('propiedad_servicio', 'propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio')
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad')
            ->where('factura.fecha_emision >=', $desde)
            ->where('factura.fecha_emision <=', $hasta);
        if ($idPropietario !== null) {
            $emitido->where('propiedad.id_propietario', $idPropietario);
        }
        $emitidoPorMes = [];
        foreach ($emitido->groupBy("DATE_FORMAT(factura.fecha_emision, '%Y-%m')")->get()->getResultArray() as $fila) {
            $emitidoPorMes[$fila['mes']] = (float) $fila['total'];
        }

        $cobrado = $this->db->table('transaccion')
            ->select("DATE_FORMAT(transaccion.fecha_pago, '%Y-%m') AS mes", false)
            ->selectSum('transaccion.monto_abonado', 'total')
            ->join('factura', 'factura.id_factura = transaccion.id_factura')
            ->join('propiedad_servicio', 'propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio')
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad')
            ->where('transaccion.estado', 'validada')
            ->where('transaccion.fecha_pago >=', $desde)
            ->where('transaccion.fecha_pago <=', $hasta);
        if ($idPropietario !== null) {
            $cobrado->where('propiedad.id_propietario', $idPropietario);
        }
        $cobradoPorMes = [];
        foreach ($cobrado->groupBy("DATE_FORMAT(transaccion.fecha_pago, '%Y-%m')")->get()->getResultArray() as $fila) {
            $cobradoPorMes[$fila['mes']] = (float) $fila['total'];
        }

        $resultado = [];
        $inicio = date_create_from_format('!Y-m-d', $desde);
        $fin = date_create_from_format('!Y-m-d', $hasta);
        if ($inicio === false || $fin === false) {
            return $resultado;
        }

        $inicio->modify('first day of this month');
        $cursor = $inicio;
        while ($cursor <= $fin) {
            $clave = $cursor->format('Y-m');
            $resultado[] = [
                'mes' => $clave,
                'emitido' => $emitidoPorMes[$clave] ?? 0.0,
                'cobrado' => $cobradoPorMes[$clave] ?? 0.0,
            ];
            $cursor->modify('+1 month');
        }

        return $resultado;
    }

    public function noLeidas(int $idUsuario): int
    {
        return (int) $this->db->table('notificacion')
            ->where('id_usuario', $idUsuario)
            ->where('leida', 0)
            ->countAllResults();
    }
}
