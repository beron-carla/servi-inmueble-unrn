<?php

namespace App\Repositories;

use App\Models\PropiedadServicioModel;
use CodeIgniter\Database\BaseConnection;

class PropiedadServicioRepository
{
    private BaseConnection $db;

    public function __construct(
        private ?PropiedadServicioModel $model = null,
        ?BaseConnection $db = null,
    ) {
        $this->db = $db ?? db_connect();
        $this->model ??= new PropiedadServicioModel();
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
    public function buscarConDetalle(int $id): ?array
    {
        return $this->db->table('propiedad_servicio')
            ->select(
                'propiedad_servicio.*, tipo_servicio.tipo, tipo_servicio.categoria, '
                . 'tipo_servicio.unidad_medida, tipo_servicio.proveedor_predeterminado, '
                . 'propiedad.alias AS alias_propiedad, propiedad.estado AS estado_propiedad, '
                . 'propiedad.id_propietario',
            )
            ->join(
                'tipo_servicio',
                'tipo_servicio.id_tipo_servicio = propiedad_servicio.id_tipo_servicio',
            )
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad')
            ->where('propiedad_servicio.id_propiedad_servicio', $id)
            ->get()
            ->getRowArray() ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarParaFactura(int $id): ?array
    {
        return $this->db->table('propiedad_servicio')
            ->select(
                'propiedad_servicio.id_propiedad_servicio, propiedad_servicio.estado, '
                . 'propiedad.id_propietario, propiedad.estado AS estado_propiedad',
            )
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad')
            ->where('propiedad_servicio.id_propiedad_servicio', $id)
            ->get()
            ->getRowArray() ?: null;
    }

    public function existeVigente(int $idPropiedad, int $idTipoServicio): bool
    {
        return $this->model
            ->where('id_propiedad', $idPropiedad)
            ->where('id_tipo_servicio', $idTipoServicio)
            ->whereIn('estado', ['activo', 'pausado', 'en revision'])
            ->countAllResults() > 0;
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function crear(array $datos): int
    {
        $id = $this->model->insert($datos, true);

        return $id === false ? 0 : (int) $id;
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function actualizar(int $id, array $datos): bool
    {
        return $this->model->update($id, $datos);
    }
}
