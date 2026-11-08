<?php

namespace App\Repositories;

use App\Models\FacturaModel;

class FacturaRepository
{
    public function __construct(private ?FacturaModel $model = null)
    {
        $this->model ??= new FacturaModel();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->model->find($id) ?: null;
    }
}
