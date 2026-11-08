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
}
