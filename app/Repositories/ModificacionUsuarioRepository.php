<?php

namespace App\Repositories;

use App\Models\ModificacionUsuarioModel;

class ModificacionUsuarioRepository
{
    public function __construct(private ?ModificacionUsuarioModel $model = null)
    {
        $this->model ??= new ModificacionUsuarioModel();
    }

    public function registrar(int $idUsuario, int $idAdmin, string $fecha): bool
    {
        return $this->model->insert([
            'id_usuario' => $idUsuario,
            'id_admin' => $idAdmin,
            'fecha_modificacion' => $fecha,
        ]) !== false;
    }
}
