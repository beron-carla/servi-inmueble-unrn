<?php

namespace App\Controllers;

use App\Services\ServicioInmuebleService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class ServiciosInmuebleController extends BaseController
{
    public function __construct(private ?ServicioInmuebleService $servicios = null)
    {
        $this->servicios ??= new ServicioInmuebleService();
    }

    public function create(int $idPropiedad): string|ResponseInterface
    {
        $resultado = $this->servicios->datosAlta(
            $idPropiedad,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }

        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound('No se encontró el inmueble solicitado.');
        }

        if ($resultado['inmueble_inactivo']) {
            session()->setFlashdata('error', 'No se pueden agregar servicios a un inmueble inactivo');

            return redirect()->to(route_to('inmuebles.show', $idPropiedad));
        }

        return view('servicios-inmueble/create', [
            'titulo' => 'Vincular servicio',
            'inmueble' => $resultado['inmueble'],
            'tipos' => $resultado['tipos'],
            'errores' => session()->getFlashdata('servicio_errores') ?? [],
            'anterior' => session()->getFlashdata('servicio_anterior') ?? [],
        ]);
    }

    public function store(int $idPropiedad): RedirectResponse|ResponseInterface
    {
        $resultado = $this->servicios->crear(
            $this->entradaFormulario(),
            $idPropiedad,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }

        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound('No se encontró el inmueble solicitado.');
        }

        if ($resultado['inmueble_inactivo']) {
            session()->setFlashdata('error', $resultado['errores']['general']);

            return redirect()->to(route_to('inmuebles.show', $idPropiedad));
        }

        if (! $resultado['exito']) {
            session()->setFlashdata('servicio_errores', $resultado['errores']);
            session()->setFlashdata('servicio_anterior', $resultado['datos']);
            session()->setFlashdata('error', reset($resultado['errores']));

            return redirect()->to(route_to('servicios.create', $idPropiedad));
        }

        session()->setFlashdata('success', 'Servicio vinculado correctamente.');

        return redirect()->to(route_to('inmuebles.show', $idPropiedad));
    }

    public function show(int $idServicio): string|ResponseInterface
    {
        $resultado = $this->servicios->detalle(
            $idServicio,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }

        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound('No se encontró el servicio solicitado.');
        }

        return view('servicios-inmueble/show', [
            'titulo' => 'Detalle del servicio',
            'servicio' => $resultado['servicio'],
        ]);
    }

    public function edit(int $idServicio): string|ResponseInterface
    {
        $resultado = $this->servicios->datosEdicion(
            $idServicio,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }

        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound('No se encontró el servicio solicitado.');
        }

        return view('servicios-inmueble/edit', [
            'titulo' => 'Modificar servicio',
            'servicio' => $resultado['servicio'],
            'tipos' => $resultado['tipos'],
            'errores' => session()->getFlashdata('servicio_errores') ?? [],
            'anterior' => session()->getFlashdata('servicio_anterior') ?? [],
        ]);
    }

    public function update(int $idServicio): RedirectResponse|ResponseInterface
    {
        $resultado = $this->servicios->actualizar(
            $idServicio,
            $this->entradaFormulario(),
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }

        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound('No se encontró el servicio solicitado.');
        }

        if (! $resultado['exito']) {
            session()->setFlashdata('servicio_errores', $resultado['errores']);
            session()->setFlashdata('servicio_anterior', $resultado['datos']);
            session()->setFlashdata('error', reset($resultado['errores']));

            return redirect()->to(route_to('servicios.edit', $idServicio));
        }

        session()->setFlashdata('success', 'Servicio actualizado correctamente.');

        return redirect()->to(route_to('servicios.show', $idServicio));
    }

    public function changeState(int $idServicio): RedirectResponse|ResponseInterface
    {
        $estado = $this->request->getPost('estado');
        $resultado = $this->servicios->cambiarEstado(
            $idServicio,
            is_string($estado) ? $estado : '',
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        return $this->responderEstado($idServicio, $resultado, 'El estado del servicio fue actualizado.');
    }

    public function deactivate(int $idServicio): RedirectResponse|ResponseInterface
    {
        $resultado = $this->servicios->darDeBaja(
            $idServicio,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        return $this->responderEstado($idServicio, $resultado, 'Servicio dado de baja correctamente.');
    }

    /**
     * @return array<string, string>
     */
    private function entradaFormulario(): array
    {
        $entrada = [];

        foreach ([
            'id_tipo_servicio',
            'codigo_pago_electronico',
            'numero_cuenta_contrato',
            'numero_medidor',
            'domicilio_facturacion',
            'titular',
        ] as $campo) {
            $valor = $this->request->getPost($campo);
            $entrada[$campo] = is_string($valor) || is_numeric($valor) ? (string) $valor : '';
        }

        return $entrada;
    }

    /**
     * @param array{exito: bool, errores: array<string, string>, sin_permiso: bool, no_encontrado: bool} $resultado
     */
    private function responderEstado(
        int $idServicio,
        array $resultado,
        string $mensajeExito,
    ): RedirectResponse|ResponseInterface {
        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }

        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound('No se encontró el servicio solicitado.');
        }

        if (! $resultado['exito']) {
            session()->setFlashdata('error', reset($resultado['errores']));

            return redirect()->to(route_to('servicios.show', $idServicio));
        }

        session()->setFlashdata('success', $mensajeExito);

        return redirect()->to(route_to('servicios.show', $idServicio));
    }

    private function pagina403(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(403)
            ->setBody(view('errors/html/error_403'));
    }
}
