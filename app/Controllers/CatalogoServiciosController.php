<?php

namespace App\Controllers;

use App\Services\TipoServicioService;

class CatalogoServiciosController extends BaseController
{
    public function __construct(private ?TipoServicioService $tipos = null)
    {
        $this->tipos ??= new TipoServicioService();
    }

    public function index(): string
    {
        $resultado = $this->tipos->listarActivos([
            'q' => $this->request->getGet('q'),
            'categoria' => $this->request->getGet('categoria'),
            'page' => $this->request->getGet('page'),
        ]);
        $resultado['pager']?->only(['q', 'categoria']);

        return view('catalogo-servicios/index', ['titulo' => 'Catálogo de servicios'] + $resultado);
    }
}
