<?php

namespace App\Repositories;

use App\Models\TransaccionModel;
use CodeIgniter\Database\BaseConnection;

final class PagoRepository
{
    private BaseConnection $db;
    private TransaccionModel $model;

    public function __construct(?TransaccionModel $model = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
        $this->model = $model ?? new TransaccionModel();
    }

    public function buscarFacturaParaPago(int $id, bool $bloquear = false): ?array
    {
        $sql = <<<'SQL'
SELECT factura.*,
       propiedad.id_propietario,
       propiedad.alias AS inmueble,
       propiedad_servicio.id_propiedad,
       propiedad_servicio.id_tipo_servicio
FROM factura
JOIN propiedad_servicio
  ON propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio
JOIN propiedad
  ON propiedad.id_propiedad = propiedad_servicio.id_propiedad
WHERE factura.id_factura = ?
LIMIT 1
SQL;

        if ($bloquear) {
            $sql .= ' FOR UPDATE';
        }

        return $this->db->query($sql, [$id])->getRowArray() ?: null;
    }

    public function existePendiente(int $idFactura): bool
    {
        return $this->db->table('transaccion')
            ->where('id_factura', $idFactura)
            ->where('estado', 'pendiente')
            ->countAllResults() > 0;
    }

    public function existeNumeroComprobante(int $idFactura, string $numero): bool
    {
        return $this->db->table('transaccion')
            ->where('id_factura', $idFactura)
            ->where('numero_comprobante', $numero)
            ->countAllResults() > 0;
    }

    public function crear(array $datos): int
    {
        $id = $this->model->insert($datos, true);

        if (! is_numeric($id) || (int) $id < 1) {
            throw new \RuntimeException('No se pudo registrar el pago.');
        }

        return (int) $id;
    }

    public function listarPropio(array $filtros, int $idPropietario): array
    {
        $builder = $this->db->table('transaccion')
            ->select([
                'transaccion.*',
                'factura.monto_total',
                'propiedad.alias AS inmueble',
            ])
            ->join('factura', 'factura.id_factura = transaccion.id_factura')
            ->join(
                'propiedad_servicio',
                'propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio',
            )
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad')
            ->where('propiedad.id_propietario', $idPropietario);

        if (($filtros['estado'] ?? '') !== '') {
            $builder->where('transaccion.estado', $filtros['estado']);
        }
        if (($filtros['desde'] ?? '') !== '') {
            $builder->where('transaccion.fecha_pago >=', $filtros['desde']);
        }
        if (($filtros['hasta'] ?? '') !== '') {
            $builder->where('transaccion.fecha_pago <=', $filtros['hasta']);
        }

        return $builder
            ->orderBy('transaccion.fecha_pago', 'DESC')
            ->orderBy('transaccion.id_transaccion', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function buscarComprobante(int $id): ?array
    {
        return $this->db->table('transaccion')
            ->select([
                'transaccion.*',
                'propiedad.id_propietario',
            ])
            ->join('factura', 'factura.id_factura = transaccion.id_factura')
            ->join(
                'propiedad_servicio',
                'propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio',
            )
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad')
            ->where('transaccion.id_transaccion', $id)
            ->get()
            ->getRowArray() ?: null;
    }

    public function listarAdmin(array $filtros): array
    {
        $builder = $this->db->table('transaccion')
            ->select([
                'transaccion.*',
                'usuario.nombre_completo AS propietario',
                'propiedad.alias AS inmueble',
                'factura.monto_total',
                'factura.periodo',
                'factura.periodo_mes',
                'factura.periodo_anio',
            ])
            ->join('usuario', 'usuario.id_usuario = transaccion.id_propietario')
            ->join('factura', 'factura.id_factura = transaccion.id_factura')
            ->join(
                'propiedad_servicio',
                'propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio',
            )
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad');

        if (($filtros['estado'] ?? '') !== '') {
            $builder->where('transaccion.estado', $filtros['estado']);
        }
        if (($filtros['propietario'] ?? '') !== '') {
            $builder->where('transaccion.id_propietario', (int) $filtros['propietario']);
        }
        if (($filtros['factura'] ?? '') !== '') {
            $builder->where('transaccion.id_factura', (int) $filtros['factura']);
        }
        if (($filtros['medio_pago'] ?? '') !== '') {
            $builder->where('transaccion.medio_pago', $filtros['medio_pago']);
        }
        if (($filtros['desde'] ?? '') !== '') {
            $builder->where('transaccion.fecha_pago >=', $filtros['desde']);
        }
        if (($filtros['hasta'] ?? '') !== '') {
            $builder->where('transaccion.fecha_pago <=', $filtros['hasta']);
        }

        return $builder
            ->orderBy('transaccion.fecha_pago', 'DESC')
            ->orderBy('transaccion.id_transaccion', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function propietariosConPagos(): array
    {
        return $this->db->table('transaccion')
            ->distinct()
            ->select([
                'usuario.id_usuario',
                'usuario.nombre_completo',
            ])
            ->join('usuario', 'usuario.id_usuario = transaccion.id_propietario')
            ->orderBy('usuario.nombre_completo', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function buscarParaValidacion(int $id, bool $bloquear = false): ?array
    {
        $sql = <<<'SQL'
SELECT transaccion.*,
       factura.estado AS estado_factura,
       factura.id_propiedad_servicio,
       propiedad.id_propietario,
       propiedad.alias AS inmueble,
       usuario.nombre_completo AS propietario,
       usuario.mail AS propietario_mail,
       factura.monto_total
FROM transaccion
JOIN factura ON factura.id_factura = transaccion.id_factura
JOIN propiedad_servicio
  ON propiedad_servicio.id_propiedad_servicio = factura.id_propiedad_servicio
JOIN propiedad ON propiedad.id_propiedad = propiedad_servicio.id_propiedad
JOIN usuario ON usuario.id_usuario = propiedad.id_propietario
WHERE transaccion.id_transaccion = ?
LIMIT 1
SQL;

        if ($bloquear) {
            $sql .= ' FOR UPDATE';
        }

        return $this->db->query($sql, [$id])->getRowArray() ?: null;
    }

    public function marcarValidada(int $idPago, int $idAdmin, string $fechaValidacion): bool
    {
        return (bool) $this->db->table('transaccion')
            ->where('id_transaccion', $idPago)
            ->update([
                'estado' => 'validada',
                'id_admin_validador' => $idAdmin,
                'fecha_validacion' => $fechaValidacion,
                'motivo_rechazo' => null,
            ]);
    }

    public function marcarRechazada(int $idPago, int $idAdmin, string $motivo, string $fechaValidacion): bool
    {
        return (bool) $this->db->table('transaccion')
            ->where('id_transaccion', $idPago)
            ->update([
                'estado' => 'rechazada',
                'id_admin_validador' => $idAdmin,
                'fecha_validacion' => $fechaValidacion,
                'motivo_rechazo' => $motivo,
            ]);
    }

    public function marcarFacturaPagada(int $idFactura): bool
    {
        return (bool) $this->db->table('factura')
            ->where('id_factura', $idFactura)
            ->update(['estado' => 'pagada']);
    }
}
