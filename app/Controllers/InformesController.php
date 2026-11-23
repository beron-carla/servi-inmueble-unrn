<?php

namespace App\Controllers;

use App\Services\InformeService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

final class InformesController extends BaseController
{
    public function __construct(
        private ?InformeService $informes = null,
    ) {
        $this->informes ??= new InformeService();
    }

    public function consumoInteranual(): string|ResponseInterface
    {
        $resultado = $this->informes->consumoInteranual(
            $this->request->getGet() ?? [],
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }
        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound(
                'No se encontró el inmueble solicitado.',
            );
        }

        return view('informes/consumo-interanual', [
            'titulo' => 'Informe de consumo interanual',
            ...$resultado,
        ]);
    }

    private function pagina403(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(403)
            ->setBody(view('errors/html/error_403'));
    }
}
