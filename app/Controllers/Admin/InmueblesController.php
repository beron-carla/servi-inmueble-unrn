<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\InmuebleService;

class InmueblesController extends BaseController
{
    public function __construct(private ?InmuebleService $inmuebles = null)
    {
        $this->inmuebles ??= new InmuebleService();
    }

    public function index(): string
    {
        $resultado = $this->inmuebles->listar([
            'ciudad' => $this->request->getGet('ciudad'),
            'tipo' => $this->request->getGet('tipo'),
            'estado' => $this->request->getGet('estado'),
            'q' => $this->request->getGet('q'),
            'propietario' => $this->request->getGet('propietario'),
            'page' => $this->request->getGet('page'),
        ], (int) session('id_usuario'), 'admin');
        $resultado['pager']?->only(['ciudad', 'tipo', 'estado', 'q', 'propietario']);

        return view('inmuebles/index', [
            'titulo' => 'Administración de inmuebles',
            'esAdmin' => true,
        ] + $resultado);
    }
}
