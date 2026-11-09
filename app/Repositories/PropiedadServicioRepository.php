<?php

namespace App\Repositories;

use App\Models\PropiedadServicioModel;

class PropiedadServicioRepository
{
    public function __construct(private ?PropiedadServicioModel $model = null)
    {
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
    public function buscarPorIdConDetalle(int $id): ?array
    {
        return $this->model
            ->select(
                'propiedad_servicio.*, propiedad.id_propietario, propiedad.alias AS propiedad_alias, '
                . 'propiedad.estado AS estado_propiedad, tipo_servicio.tipo, tipo_servicio.categoria, '
                . 'tipo_servicio.unidad_medida, tipo_servicio.proveedor_predeterminado',
            )
            ->join('propiedad', 'propiedad.id_propiedad = propiedad_servicio.id_propiedad')
            ->join('tipo_servicio', 'tipo_servicio.id_tipo_servicio = propiedad_servicio.id_tipo_servicio')
            ->where('propiedad_servicio.id_propiedad_servicio', $id)
            ->first() ?: null;
    }

    public function existeVigente(
        int $idPropiedad,
        int $idTipoServicio,
        ?int $idExcluir = null,
    ): bool {
        $consulta = $this->model
            ->where('id_propiedad', $idPropiedad)
            ->where('id_tipo_servicio', $idTipoServicio)
            ->whereIn('estado', ['activo', 'pausado', 'en revision']);

        if ($idExcluir !== null) {
            $consulta->where('id_propiedad_servicio <>', $idExcluir);
        }

        return $consulta->countAllResults() > 0;
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
