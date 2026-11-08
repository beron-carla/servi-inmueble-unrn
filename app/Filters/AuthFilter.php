<?php

namespace App\Filters;

use App\Repositories\UsuarioRepository;
use App\Services\SesionService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

class AuthFilter implements FilterInterface
{
    public function __construct(
        private ?SesionService $sesiones = null,
        private ?UsuarioRepository $usuarios = null,
    ) {
        $this->sesiones ??= new SesionService();
        $this->usuarios ??= new UsuarioRepository();
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $userId = (int) $session->get('id_usuario');
        $sessionId = (int) $session->get('id_sesion');
        $token = (string) $session->get('token');

        if ($userId < 1 || $sessionId < 1 || $token === '') {
            $this->destruirSesion();

            return redirect()->to(site_url('auth/login'));
        }

        $row = $this->sesiones->buscarValida($sessionId, $userId, $token);

        if ($row === null) {
            $this->destruirSesion();

            return redirect()->to(site_url('auth/login'));
        }

        $lastActivity = $row['ultima_actividad'] ?? $row['fecha_creacion'];
        $lastActivityTime = strtotime((string) $lastActivity);

        $expiration = (int) config('Session')->expiration;

        if ($lastActivityTime === false || $lastActivityTime <= time() - $expiration) {
            if (! $this->sesiones->invalidar($sessionId)) {
                throw new RuntimeException('No se pudo invalidar una sesión vencida.');
            }

            $this->destruirSesion();

            return redirect()->to(site_url('auth/login'));
        }

        $user = $this->usuarios->buscarPorId($userId);

        if ($user === null || $user['estado'] !== 'activo') {
            if (! $this->sesiones->invalidarPorUsuario($userId)) {
                throw new RuntimeException('No se pudieron invalidar las sesiones del usuario.');
            }

            $this->destruirSesion();

            return redirect()->to(site_url('auth/login'));
        }

        $path = trim($request->getUri()->getPath(), '/');

        if ((int) $user['cambiar_contrasenia'] === 1 && $path !== 'auth/cambiar-contrasenia') {
            return redirect()->to(site_url('auth/cambiar-contrasenia'));
        }

        if (! $this->sesiones->actualizarActividad($sessionId)) {
            throw new RuntimeException('No se pudo actualizar la actividad de la sesión.');
        }
    }

    private function destruirSesion(): void
    {
        session()->remove(['id_usuario', 'id_sesion', 'token', 'rol', 'nombre_completo']);
        session()->destroy();
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
