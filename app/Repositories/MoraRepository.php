<?php

namespace App\Repositories;

use CodeIgniter\Database\BaseConnection;

final class MoraRepository
{
    private const POR_PAGINA = 20;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @return list<array{id_usuario: int|string, nombre_completo: string}>
     */
    public function opcionesPropietarios(): array
    {
        return $this->db->table('usuario')
            ->select('id_usuario, nombre_completo')
            ->where('rol', 'propietario')
            ->where('estado', 'activo')
            ->orderBy('nombre_completo', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return list<array{id_propiedad: int|string, alias: string}>
     */
    public function opcionesInmuebles(?int $idPropietario): array
    {
        $consulta = $this->db->table('propiedad')
            ->distinct()
            ->select('propiedad.id_propiedad, propiedad.alias')
            ->join('propiedad_servicio', 'propiedad_servicio.id_propiedad = propiedad.id_propiedad')
            ->join('factura', 'factura.id_propiedad_servicio = propiedad_servicio.id_propiedad_servicio');

        if ($idPropietario !== null && $idPropietario > 0) {
            $consulta->where('propiedad.id_propietario', $idPropietario);
        }

        return $consulta
            ->orderBy('propiedad.alias', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * @param array<string, string> $filtros
     * @return array{facturas: list<array<string, mixed>>, pagina: int, paginas: int, total: int}
     */
    public function listar(
        array $filtros,
        ?int $idPropietario,
        int $pagina,
        string $hoy,
    ): array {
        [$where, $parametros] = $this->whereMora($filtros, $idPropietario, $hoy);

        $conteo = $this->db->query(
            'SELECT COUNT(*) AS total '
            . 'FROM factura '
            . 'JOIN propiedad_servicio ON propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio '
            . 'JOIN propiedad ON propiedad.id_propiedad = propiedad_servicio.id_propiedad '
            . $where,
            $parametros,
        )->getRowArray();

        $total = (int) ($conteo['total'] ?? 0);
        $paginas = max(1, (int) ceil($total / self::POR_PAGINA));
        $pagina = min(max(1, $pagina), $paginas);
        $offset = ($pagina - 1) * self::POR_PAGINA;

        $sql = 'SELECT factura.id_factura, '
            . 'usuario.nombre_completo AS propietario, '
            . 'propiedad.alias AS inmueble, '
            . 'tipo_servicio.tipo AS servicio, '
            . 'factura.periodo, factura.periodo_mes, factura.periodo_anio, '
            . 'COALESCE(factura.fecha_segundo_vencimiento, factura.fecha_primer_vencimiento) AS vencimiento_aplicable, '
            . 'factura.monto_total, '
            . 'DATEDIFF(?, COALESCE(factura.fecha_segundo_vencimiento, factura.fecha_primer_vencimiento)) AS dias_mora '
            . 'FROM factura '
            . 'JOIN propiedad_servicio ON propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio '
            . 'JOIN propiedad ON propiedad.id_propiedad = propiedad_servicio.id_propiedad '
            . 'JOIN usuario ON usuario.id_usuario = propiedad.id_propietario '
            . 'JOIN tipo_servicio ON tipo_servicio.id_tipo_servicio = propiedad_servicio.id_tipo_servicio '
            . $where
            . ' ORDER BY dias_mora DESC, factura.id_factura DESC '
            . 'LIMIT ' . self::POR_PAGINA . ' OFFSET ' . $offset;

        $facturas = $this->db->query($sql, array_merge([$hoy], $parametros))->getResultArray();

        return [
            'facturas' => $facturas,
            'pagina' => $pagina,
            'paginas' => $paginas,
            'total' => $total,
        ];
    }

    /**
     * @return array{cantidad: int, monto: float, pagos_tardios: int}
     */
    public function metricas(?int $idPropietario): array
    {
        $parametros = [];
        $joinOwner = '';
        $whereOwner = '';

        if ($idPropietario !== null && $idPropietario > 0) {
            $joinOwner = 'JOIN propiedad_servicio ps ON ps.id_propiedad_servicio = factura.id_propiedad_servicio '
                . 'JOIN propiedad p ON p.id_propiedad = ps.id_propiedad ';
            $whereOwner = ' AND p.id_propietario = ?';
            $parametros[] = $idPropietario;
        }

        $hoy = date('Y-m-d');
        $row = $this->db->query(
            'SELECT COUNT(*) AS cantidad, COALESCE(SUM(factura.monto_total), 0) AS monto '
            . 'FROM factura '
            . $joinOwner
            . "WHERE factura.estado = 'pendiente' "
            . 'AND COALESCE(factura.fecha_segundo_vencimiento, factura.fecha_primer_vencimiento) < ?'
            . $whereOwner,
            array_merge([$hoy], $parametros),
        )->getRowArray();

        $pagosParam = [];
        $pagosJoin = '';
        $pagosOwner = '';
        if ($idPropietario !== null && $idPropietario > 0) {
            $pagosJoin = 'JOIN factura f ON f.id_factura = transaccion.id_factura '
                . 'JOIN propiedad_servicio ps ON ps.id_propiedad_servicio = f.id_propiedad_servicio '
                . 'JOIN propiedad p ON p.id_propiedad = ps.id_propiedad ';
            $pagosOwner = ' AND p.id_propietario = ?';
            $pagosParam[] = $idPropietario;
        }
        $pagos = $this->db->query(
            'SELECT COUNT(*) AS total '
            . 'FROM transaccion '
            . $pagosJoin
            . 'WHERE transaccion.en_mora = 1'
            . $pagosOwner,
            $pagosParam,
        )->getRowArray();

        return [
            'cantidad' => (int) ($row['cantidad'] ?? 0),
            'monto' => (float) ($row['monto'] ?? 0),
            'pagos_tardios' => (int) ($pagos['total'] ?? 0),
        ];
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function whereMora(
        array $filtros,
        ?int $idPropietario,
        string $hoy,
    ): array {
        $where = "WHERE factura.estado = 'pendiente' "
            . 'AND COALESCE(factura.fecha_segundo_vencimiento, factura.fecha_primer_vencimiento) < ? ';
        $parametros = [$hoy];

        if ($idPropietario !== null && $idPropietario > 0) {
            $where .= 'AND propiedad.id_propietario = ? ';
            $parametros[] = $idPropietario;
        }
        if (($filtros['inmueble'] ?? '') !== '') {
            $where .= 'AND propiedad.id_propiedad = ? ';
            $parametros[] = (int) $filtros['inmueble'];
        }
        if (($filtros['desde'] ?? '') !== '') {
            $where .= 'AND COALESCE(factura.fecha_segundo_vencimiento, factura.fecha_primer_vencimiento) >= ? ';
            $parametros[] = $filtros['desde'];
        }
        if (($filtros['hasta'] ?? '') !== '') {
            $where .= 'AND COALESCE(factura.fecha_segundo_vencimiento, factura.fecha_primer_vencimiento) <= ? ';
            $parametros[] = $filtros['hasta'];
        }
        if (($filtros['dias_min'] ?? '') !== '') {
            $where .= 'AND DATEDIFF(?, COALESCE(factura.fecha_segundo_vencimiento, factura.fecha_primer_vencimiento)) >= ? ';
            $parametros[] = $hoy;
            $parametros[] = (int) $filtros['dias_min'];
        }
        if (($filtros['dias_max'] ?? '') !== '') {
            $where .= 'AND DATEDIFF(?, COALESCE(factura.fecha_segundo_vencimiento, factura.fecha_primer_vencimiento)) <= ? ';
            $parametros[] = $hoy;
            $parametros[] = (int) $filtros['dias_max'];
        }

        return [$where, $parametros];
    }
}
