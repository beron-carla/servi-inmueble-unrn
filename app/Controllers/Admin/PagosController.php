<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\ValidacionPagoService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

final class PagosController extends BaseController
{
    public function __construct(private ?ValidacionPagoService $pagos = null)
    {
        $this->pagos ??= new ValidacionPagoService();
    }

    public function index(): string|ResponseInterface
    {
        $resultado = $this->pagos->listado(
            $this->request->getGet() ?? [],
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }

        return view('admin/pagos/index', [
            'titulo' => 'Validación de pagos',
            'pagos' => $resultado['pagos'],
            'filtros' => $resultado['filtros'],
            'propietarios' => $resultado['propietarios'],
        ]);
    }

    public function show(int $idTransaccion): string|ResponseInterface
    {
        $resultado = $this->pagos->detalle(
            $idTransaccion,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }
        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound('No se encontró el pago solicitado.');
        }

        return view('admin/pagos/show', [
            'titulo' => 'Detalle del pago',
            'pago' => $resultado['pago'],
            'errores' => session()->getFlashdata('rechazo_errores') ?? [],
            'filtros' => $this->request->getGet() ?? [],
        ]);
    }

    public function validar(int $idTransaccion): RedirectResponse|ResponseInterface
    {
        $resultado = $this->pagos->validar(
            $idTransaccion,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }
        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound('No se encontró el pago solicitado.');
        }
        if ($resultado['ya_procesado']) {
            session()->setFlashdata('error', 'El pago ya fue procesado');
        } else {
            session()->setFlashdata('success', 'Pago validado correctamente.');
        }

        return redirect()->to(route_to('admin.pagos.index'));
    }

    public function rechazar(int $idTransaccion): RedirectResponse|ResponseInterface
    {
        $motivo = (string) $this->request->getPost('motivo');
        $resultado = $this->pagos->rechazar(
            $idTransaccion,
            (int) session('id_usuario'),
            $motivo,
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }
        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound('No se encontró el pago solicitado.');
        }
        if ($resultado['ya_procesado']) {
            session()->setFlashdata('error', 'El pago ya fue procesado');

            return redirect()->to(route_to('admin.pagos.show', $idTransaccion));
        }
        if ($resultado['errores'] !== []) {
            session()->setFlashdata('rechazo_errores', $resultado['errores']);

            return redirect()->to(route_to('admin.pagos.show', $idTransaccion));
        }

        session()->setFlashdata('success', 'Pago rechazado correctamente.');

        return redirect()->to(route_to('admin.pagos.index'));
    }

    private function pagina403(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(403)
            ->setBody(view('errors/html/error_403'));
    }
}
