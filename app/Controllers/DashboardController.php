<?php

namespace App\Controllers;

use App\Services\NotificacionService;

class DashboardController extends BaseController
{
    public function __construct(private ?NotificacionService $notificaciones = null)
    {
        $this->notificaciones ??= new NotificacionService();
    }

    public function index(): string
    {
        $this->notificaciones->registrarVencimientos();

        return view('dashboard/index', ['titulo' => 'Panel de control']);
    }
}
