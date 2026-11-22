<?php

namespace App\Controllers;

use App\Services\NotificacionService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class NotificacionesController extends BaseController
{
    public function __construct(private ?NotificacionService $notificaciones = null)
    {
        $this->notificaciones ??= new NotificacionService();
    }

    public function index(): string
    {
        $resultado = $this->notificaciones->listado(
            $this->request->getGet() ?? [],
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        return view('notificaciones/index', [
            'titulo' => 'Notificaciones',
            ...$resultado,
        ]);
    }

    public function marcarLeida(int $idNotificacion): RedirectResponse|ResponseInterface
    {
        $resultado = $this->notificaciones->marcarLeida(
            $idNotificacion,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }
        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound('No se encontró la notificación solicitada.');
        }

        return redirect()->to(route_to('notificaciones.index'));
    }

    public function marcarTodasLeidas(): RedirectResponse|ResponseInterface
    {
        if ((string) session('rol') === 'admin') {
            return $this->pagina403();
        }

        $this->notificaciones->marcarTodasLeidas((int) session('id_usuario'));

        return redirect()->to(route_to('notificaciones.index'));
    }

    private function pagina403(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(403)
            ->setBody(view('errors/html/error_403'));
    }
}
