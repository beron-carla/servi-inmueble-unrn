<?php

namespace App\Controllers;

use App\Services\MoraService;

class MoraController extends BaseController
{
    public function __construct(private ?MoraService $mora = null)
    {
        $this->mora ??= new MoraService();
    }

    public function index(): string
    {
        $resultado = $this->mora->listado(
            $this->request->getGet() ?? [],
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        return view('mora/index', [
            'titulo' => 'Control de mora',
            ...$resultado,
        ]);
    }
}
