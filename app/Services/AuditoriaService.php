<?php

namespace App\Services;

use App\Repositories\ModificacionUsuarioRepository;
use RuntimeException;

class AuditoriaService
{
    public function __construct(private ?ModificacionUsuarioRepository $modificaciones = null)
    {
        $this->modificaciones ??= new ModificacionUsuarioRepository();
    }

    public function registrarModificacion(int $idUsuario, int $idAdmin): void
    {
        if (! $this->modificaciones->registrar($idUsuario, $idAdmin, date('Y-m-d H:i:s'))) {
            throw new RuntimeException('No se pudo registrar la modificación del usuario.');
        }
    }
}
