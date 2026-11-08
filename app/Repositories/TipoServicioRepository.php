<?php

namespace App\Repositories;

use App\Models\TipoServicioModel;

class TipoServicioRepository
{
    public function __construct(private ?TipoServicioModel $model = null)
    {
        $this->model ??= new TipoServicioModel();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->model->find($id) ?: null;
    }

    public function existePorTipoYProveedor(string $tipo, string $proveedor): bool
    {
        return $this->model
            ->where('tipo', $tipo)
            ->where('proveedor_predeterminado', $proveedor)
            ->countAllResults() > 0;
    }

    /**
     * @param array{tipo: string, categoria: string, unidad_medida: string, proveedor_predeterminado: string, activo: int} $datos
     */
    public function crear(array $datos): bool
    {
        return $this->model->insert($datos) !== false;
    }
}
