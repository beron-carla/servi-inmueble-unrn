<?php

namespace App\Services;

use App\Repositories\SesionRepository;
use CodeIgniter\Session\SessionInterface;
use RuntimeException;

class SesionService
{
    private SesionRepository $sesiones;

    private SessionInterface $session;

    public function __construct(?SesionRepository $sesiones = null, ?SessionInterface $session = null)
    {
        $this->sesiones = $sesiones ?? new SesionRepository();
        $this->session = $session ?? session();
    }

    /**
     * @param array<string, mixed> $usuario
     */
    public function iniciar(array $usuario): void
    {
        $token = bin2hex(random_bytes(32));
        $idSesion = $this->sesiones->crear((int) $usuario['id_usuario'], $token);

        if ($idSesion === false || $idSesion < 1) {
            throw new RuntimeException('No se pudo iniciar la sesión.');
        }

        $this->session->regenerate(true);
        $this->session->set([
            'id_usuario'     => (int) $usuario['id_usuario'],
            'id_sesion'      => $idSesion,
            'token'          => $token,
            'rol'            => (string) $usuario['rol'],
            'nombre_completo' => (string) $usuario['nombre_completo'],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarValida(int $idSesion, int $idUsuario, string $token): ?array
    {
        return $this->sesiones->buscarValida($idSesion, $idUsuario, $token);
    }

    public function actualizarActividad(int $idSesion): bool
    {
        return $this->sesiones->actualizarActividad($idSesion);
    }

    public function regenerar(): void
    {
        $this->session->regenerate(true);
    }

    public function invalidarPorUsuario(int $idUsuario): bool
    {
        return $this->sesiones->eliminarPorUsuario($idUsuario);
    }

    public function invalidar(int $idSesion): bool
    {
        return $this->sesiones->eliminar($idSesion);
    }

    public function cerrarSesion(): void
    {
        $idSesion = (int) $this->session->get('id_sesion');

        if ($idSesion > 0 && ! $this->invalidar($idSesion)) {
            throw new RuntimeException('No se pudo invalidar la sesión.');
        }

        $this->session->remove(['id_usuario', 'id_sesion', 'token', 'rol', 'nombre_completo']);
        $this->session->destroy();
    }
}
