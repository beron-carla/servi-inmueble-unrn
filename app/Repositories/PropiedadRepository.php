<?php

namespace App\Repositories;

use App\Models\PropiedadModel;

class PropiedadRepository
{
    public function __construct(private ?PropiedadModel $model = null)
    {
        $this->model ??= new PropiedadModel();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->model->find($id) ?: null;
    }
}
