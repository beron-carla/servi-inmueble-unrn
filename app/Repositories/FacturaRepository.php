<?php

namespace App\Repositories;

use App\Models\FacturaModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;

class FacturaRepository
{
    private const FACTURAS_POR_PAGINA = 20;

    private BaseConnection $db;

    public function __construct(
        private ?FacturaModel $model = null,
        ?BaseConnection $db = null,
    ) {
        $this->db = $db ?? db_connect();
        $this->model ??= new FacturaModel();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->model->find($id) ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarServicioParaAlta(int $idServicio, bool $bloquear = false): ?array
    {
        $sql = <<<'SQL'
SELECT propiedad_servicio.*,
       propiedad.id_propietario,
       propiedad.estado AS estado_propiedad,
       propiedad.alias AS alias_propiedad,
       tipo_servicio.tipo,
       tipo_servicio.categoria,
       tipo_servicio.unidad_medida,
       tipo_servicio.proveedor_predeterminado
FROM propiedad_servicio
JOIN propiedad
  ON propiedad.id_propiedad = propiedad_servicio.id_propiedad
JOIN tipo_servicio
  ON tipo_servicio.id_tipo_servicio = propiedad_servicio.id_tipo_servicio
WHERE propiedad_servicio.id_propiedad_servicio = ?
LIMIT 1
SQL;

        if ($bloquear) {
            $sql .= ' FOR UPDATE';
        }

        return $this->db->query($sql, [$idServicio])->getRowArray() ?: null;
    }

    public function existePeriodo(int $idServicio, string $periodo, int $anio, string $mes): bool
    {
        return $this->db->table('factura')
            ->where('id_propiedad_servicio', $idServicio)
            ->where('periodo', $periodo)
            ->where('periodo_anio', $anio)
            ->where('periodo_mes', $mes)
            ->countAllResults() > 0;
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function crear(array $datos): int|false
    {
        $id = $this->model->insert($datos, true);

        return $id === false ? false : (int) $id;
    }

    /**
     * @param array{inmueble: string, servicio: string, estado: string, desde: string, hasta: string} $filtros
     * @return array{
     *     facturas: list<array<string, mixed>>,
     *     resumen: array<string, string>,
     *     pagina: int,
     *     paginas: int,
     *     total: int,
     *     por_pagina: int
     * }
     */
    public function listar(array $filtros, ?int $idPropietario, int $pagina, string $hoy): array
    {
        $total = $this->consultaListado($filtros, $idPropietario, $hoy)->countAllResults();
        $paginas = max(1, (int) ceil($total / self::FACTURAS_POR_PAGINA));
        $pagina = min(max(1, $pagina), $paginas);
        $desde = '(COALESCE(factura.fecha_segundo_vencimiento, factura.fecha_primer_vencimiento))';

        $facturas = $this->consultaListado($filtros, $idPropietario, $hoy)
            ->select([
                'factura.id_factura',
                'propiedad.id_propiedad',
                'propiedad.alias AS inmueble',
                'direccion.calle',
                'direccion.numero',
                'tipo_servicio.tipo AS tipo_servicio',
                'factura.periodo',
                'factura.periodo_mes',
                'factura.periodo_anio',
                'factura.proveedor',
                'factura.monto_total',
                'factura.estado',
            ])
            ->select($desde . ' AS vencimiento_aplicable', false)
            ->select($this->expresionEstado($hoy) . ' AS estado_calculado', false)
            ->orderBy($desde, 'DESC', false)
            ->orderBy('factura.id_factura', 'DESC')
            ->get(self::FACTURAS_POR_PAGINA, ($pagina - 1) * self::FACTURAS_POR_PAGINA)
            ->getResultArray();

        $resumen = $this->consultaListado($filtros, $idPropietario, $hoy)
            ->select(
                "COALESCE(SUM(CASE WHEN factura.estado = 'pendiente' THEN factura.monto_total ELSE 0 END), 0) AS total_pendiente",
                false,
            )
            ->select(
                "COALESCE(SUM(CASE WHEN factura.estado = 'pagada' THEN factura.monto_total ELSE 0 END), 0) AS total_pagado",
                false,
            )
            ->select('COALESCE(SUM(factura.monto_total), 0) AS total_general', false)
            ->get()
            ->getRowArray();

        return [
            'facturas' => $facturas,
            'resumen' => [
                'total_pendiente' => (string) ($resumen['total_pendiente'] ?? '0.00'),
                'total_pagado' => (string) ($resumen['total_pagado'] ?? '0.00'),
                'total_general' => (string) ($resumen['total_general'] ?? '0.00'),
            ],
            'pagina' => $pagina,
            'paginas' => $paginas,
            'total' => $total,
            'por_pagina' => self::FACTURAS_POR_PAGINA,
        ];
    }

    /**
     * @return list<array{id_propiedad: int|string, alias: string}>
     */
    public function opcionesPropiedades(?int $idPropietario): array
    {
        $consulta = $this->db->table('propiedad')
            ->distinct()
            ->select('propiedad.id_propiedad, propiedad.alias')
            ->join('propiedad_servicio', 'propiedad_servicio.id_propiedad = propiedad.id_propiedad')
            ->join(
                'factura',
                'factura.id_propiedad_servicio = propiedad_servicio.id_propiedad_servicio',
            );

        if ($idPropietario !== null) {
            $consulta->where('propiedad.id_propietario', $idPropietario);
        }

        return $consulta
            ->orderBy('propiedad.alias', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array{
     *     id_propiedad_servicio: int|string,
     *     alias: string,
     *     tipo: string,
     *     proveedor: string
     * }>
     */
    public function opcionesServicios(?int $idPropietario): array
    {
        $consulta = $this->db->table('propiedad_servicio')
            ->distinct()
            ->select([
                'propiedad_servicio.id_propiedad_servicio',
                'propiedad.alias',
                'tipo_servicio.tipo',
                'tipo_servicio.proveedor_predeterminado AS proveedor',
            ])
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad')
            ->join(
                'tipo_servicio',
                'tipo_servicio.id_tipo_servicio = propiedad_servicio.id_tipo_servicio',
            )
            ->join(
                'factura',
                'factura.id_propiedad_servicio = propiedad_servicio.id_propiedad_servicio',
            );

        if ($idPropietario !== null) {
            $consulta->where('propiedad.id_propietario', $idPropietario);
        }

        return $consulta
            ->orderBy('propiedad.alias', 'ASC')
            ->orderBy('tipo_servicio.tipo', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @param array{inmueble: string, servicio: string, estado: string, desde: string, hasta: string} $filtros
     */
    private function consultaListado(array $filtros, ?int $idPropietario, string $hoy): BaseBuilder
    {
        $consulta = $this->db->table('factura')
            ->join(
                'propiedad_servicio',
                'propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio',
            )
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad')
            ->join('direccion', 'direccion.id_direccion = propiedad.id_direccion')
            ->join(
                'tipo_servicio',
                'tipo_servicio.id_tipo_servicio = propiedad_servicio.id_tipo_servicio',
            );

        if ($idPropietario !== null) {
            $consulta->where('propiedad.id_propietario', $idPropietario);
        }

        if ($filtros['inmueble'] !== '') {
            $consulta->where('propiedad.id_propiedad', (int) $filtros['inmueble']);
        }

        if ($filtros['servicio'] !== '') {
            $consulta->where(
                'propiedad_servicio.id_propiedad_servicio',
                (int) $filtros['servicio'],
            );
        }

        if ($filtros['estado'] !== '') {
            $consulta->where(
                $this->expresionEstado($hoy) . ' = ' . $this->db->escape($filtros['estado']),
                null,
                false,
            );
        }

        $vencimiento = 'COALESCE(factura.fecha_segundo_vencimiento, factura.fecha_primer_vencimiento)';

        if ($filtros['desde'] !== '') {
            $consulta->where(
                $vencimiento . ' >= ' . $this->db->escape($filtros['desde']),
                null,
                false,
            );
        }

        if ($filtros['hasta'] !== '') {
            $consulta->where(
                $vencimiento . ' <= ' . $this->db->escape($filtros['hasta']),
                null,
                false,
            );
        }

        return $consulta;
    }

    private function expresionEstado(string $hoy): string
    {
        return "CASE WHEN factura.estado = 'pagada' THEN 'pagada' "
            . "WHEN COALESCE(factura.fecha_segundo_vencimiento, factura.fecha_primer_vencimiento) < "
            . $this->db->escape($hoy)
            . " THEN 'vencida' ELSE 'pendiente' END";
    }
}
