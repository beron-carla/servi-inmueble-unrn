<?php

namespace App\Controllers;

use App\Services\NotificacionService;
use App\Services\DashboardService;
use CodeIgniter\HTTP\ResponseInterface;

class DashboardController extends BaseController
{
    public function __construct(
        private ?NotificacionService $notificaciones = null,
        private ?DashboardService $dashboard = null,
    ) {
        $this->notificaciones ??= new NotificacionService();
        $this->dashboard ??= new DashboardService();
    }

    public function index(): string
    {
        $this->notificaciones->registrarVencimientos();
        $metricas = $this->dashboard->metricas(
            (int) session()->get('id_usuario'),
            (string) session()->get('rol'),
        );

        return view('dashboard/index', [
            'titulo' => 'Panel de Servicios',
            'metricas' => $metricas,
        ]);
    }
}
