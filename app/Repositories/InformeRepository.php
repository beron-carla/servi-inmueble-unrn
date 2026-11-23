<?php

namespace App\Repositories;

use CodeIgniter\Database\BaseConnection;

final class InformeRepository
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @return list<array{id_propiedad: int|string, alias: string}>
     */
    public function opcionesInmuebles(?int $idPropietario): array
    {
        $consulta = $this->db->table('propiedad')
            ->distinct()
            ->select('propiedad.id_propiedad, propiedad.alias')
            ->join('propiedad_servicio', 'propiedad_servicio.id_propiedad = propiedad.id_propiedad');

        if ($idPropietario !== null) {
            $consulta->where('propiedad.id_propietario', $idPropietario);
        }

        return $consulta
            ->orderBy('propiedad.alias', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array{id_propiedad_servicio: int|string, tipo: string, unidad_medida: string}>
     */
    public function opcionesServicios(?int $idPropietario, ?int $idPropiedad): array
    {
        $consulta = $this->db->table('propiedad_servicio')
            ->distinct()
            ->select([
                'propiedad_servicio.id_propiedad_servicio',
                'tipo_servicio.tipo',
                'tipo_servicio.unidad_medida',
            ])
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad')
            ->join('tipo_servicio', 'tipo_servicio.id_tipo_servicio = propiedad_servicio.id_tipo_servicio');

        if ($idPropietario !== null) {
            $consulta->where('propiedad.id_propietario', $idPropietario);
        }
        if ($idPropiedad !== null) {
            $consulta->where('propiedad.id_propiedad', $idPropiedad);
        }

        return $consulta
            ->orderBy('tipo_servicio.tipo', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPropiedad(int $idPropiedad): ?array
    {
        return $this->db->table('propiedad')
            ->where('id_propiedad', $idPropiedad)
            ->get()
            ->getRowArray() ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarServicio(int $idServicio, int $idPropiedad): ?array
    {
        return $this->db->table('propiedad_servicio')
            ->select([
                'propiedad_servicio.id_propiedad_servicio',
                'propiedad_servicio.id_propiedad',
                'tipo_servicio.unidad_medida',
            ])
            ->join('tipo_servicio', 'tipo_servicio.id_tipo_servicio = propiedad_servicio.id_tipo_servicio')
            ->where('propiedad_servicio.id_propiedad_servicio', $idServicio)
            ->where('propiedad_servicio.id_propiedad', $idPropiedad)
            ->get()
            ->getRowArray() ?: null;
    }

    /**
     * @return array{filas: list<array<string, mixed>>, hay_datos: bool}
     */
    public function compararMensual(
        int $idPropiedad,
        int $anioBase,
        int $anioComparado,
        ?int $idServicio,
        ?string $unidad,
    ): array {
        $consulta = $this->db->table('factura')
            ->select([
                'factura.periodo_anio',
                'factura.periodo_mes',
            ])
            ->select(
                $unidad === 'Moneda fija'
                    ? 'SUM(factura.monto_total) AS valor'
                    : 'SUM(factura.consumo_del_periodo) AS valor',
                false,
            )
            ->join(
                'propiedad_servicio',
                'propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio',
            )
            ->where('propiedad_servicio.id_propiedad', $idPropiedad)
            ->where('factura.periodo', 'mes')
            ->whereIn('factura.periodo_anio', [$anioBase, $anioComparado]);

        if ($idServicio !== null) {
            $consulta->where('factura.id_propiedad_servicio', $idServicio);
        }
        if ($unidad !== null) {
            $consulta->where('factura.unidad_medida', $unidad);
        }

        $filas = $consulta
            ->groupBy(['factura.periodo_anio', 'factura.periodo_mes'])
            ->orderBy('factura.periodo_anio', 'ASC')
            ->orderBy('factura.periodo_mes', 'ASC')
            ->get()
            ->getResultArray();

        return [
            'filas' => $filas,
            'hay_datos' => $filas !== [],
        ];
    }

    /**
     * @return list<array{periodo_anio: int|string, cantidad: int, valor: float}>
     */
    public function resumenAnual(
        int $idPropiedad,
        int $anioBase,
        int $anioComparado,
        ?int $idServicio,
        ?string $unidad,
    ): array {
        $consulta = $this->db->table('factura')
            ->select([
                'factura.periodo_anio',
                'COUNT(*) AS cantidad',
            ])
            ->select(
                $unidad === 'Moneda fija'
                    ? 'SUM(factura.monto_total) AS valor'
                    : 'SUM(factura.consumo_del_periodo) AS valor',
                false,
            )
            ->join(
                'propiedad_servicio',
                'propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio',
            )
            ->where('propiedad_servicio.id_propiedad', $idPropiedad)
            ->where('factura.periodo', 'anio')
            ->whereIn('factura.periodo_anio', [$anioBase, $anioComparado]);

        if ($idServicio !== null) {
            $consulta->where('factura.id_propiedad_servicio', $idServicio);
        }
        if ($unidad !== null) {
            $consulta->where('factura.unidad_medida', $unidad);
        }

        return $consulta
            ->groupBy('factura.periodo_anio')
            ->orderBy('factura.periodo_anio', 'ASC')
            ->get()
            ->getResultArray();
    }
}
