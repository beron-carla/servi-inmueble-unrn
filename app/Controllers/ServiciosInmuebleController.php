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
        $resultado = $this->servicios->opcionesAlta(
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

        return view('servicios-inmueble/form', [
            'titulo' => 'Vincular servicio',
            'inmueble' => $resultado['inmueble'],
            'tipos' => $resultado['tipos'],
            'sinTipos' => $resultado['sin_tipos'],
            'servicio' => null,
            'accion' => route_to('servicios.store', $idPropiedad),
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
            session()->setFlashdata('error', 'No se pueden agregar servicios a un inmueble inactivo');

            return redirect()->to(route_to('inmuebles.show', $idPropiedad));
        }

        if (! $resultado['exito']) {
            session()->setFlashdata('servicio_errores', $resultado['errores']);
            session()->setFlashdata('servicio_anterior', $resultado['datos']);
            session()->setFlashdata('error', reset($resultado['errores']));

            return redirect()->to(route_to('servicios.create', $idPropiedad));
        }

        session()->setFlashdata('success', 'Servicio vinculado correctamente.');

        return redirect()->to(route_to('servicios.show', $resultado['id_propiedad_servicio']));
    }

    public function show(int $id): string|ResponseInterface
    {
        $resultado = $this->servicios->detalle(
            $id,
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

    public function edit(int $id): string|ResponseInterface
    {
        $resultado = $this->servicios->detalle(
            $id,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }

        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound('No se encontró el servicio solicitado.');
        }

        return view('servicios-inmueble/form', [
            'titulo' => 'Modificar datos del servicio',
            'inmueble' => [
                'id_propiedad' => $resultado['servicio']['id_propiedad'],
                'alias' => $resultado['servicio']['alias_propiedad'],
                'estado' => $resultado['servicio']['estado_propiedad'],
            ],
            'tipos' => [],
            'sinTipos' => false,
            'servicio' => $resultado['servicio'],
            'accion' => route_to('servicios.update', $id),
            'errores' => session()->getFlashdata('servicio_errores') ?? [],
            'anterior' => session()->getFlashdata('servicio_anterior') ?? [],
        ]);
    }

    public function update(int $id): RedirectResponse|ResponseInterface
    {
        $resultado = $this->servicios->actualizar(
            $id,
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

            return redirect()->to(route_to('servicios.edit', $id));
        }

        session()->setFlashdata('success', 'Datos del servicio actualizados correctamente.');

        return redirect()->to(route_to('servicios.show', $id));
    }

    public function changeState(int $id): RedirectResponse|ResponseInterface
    {
        $estado = $this->request->getPost('estado');
        $nuevoEstado = is_string($estado) ? $estado : '';
        $resultado = $this->servicios->cambiarEstado(
            $id,
            $nuevoEstado,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        return $this->redirigirCambioEstado($id, $resultado, $nuevoEstado);
    }

    public function deactivate(int $id): RedirectResponse|ResponseInterface
    {
        $resultado = $this->servicios->cambiarEstado(
            $id,
            'inactivo',
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        return $this->redirigirCambioEstado($id, $resultado, 'inactivo');
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
     * @param array<string, mixed> $resultado
     */
    private function redirigirCambioEstado(
        int $id,
        array $resultado,
        string $estado,
    ): RedirectResponse|ResponseInterface {
        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }

        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound('No se encontró el servicio solicitado.');
        }

        if (! $resultado['exito']) {
            session()->setFlashdata('error', reset($resultado['errores']));
        } else {
            $mensaje = $estado === 'inactivo'
                ? 'Servicio dado de baja correctamente.'
                : 'Estado del servicio actualizado correctamente.';
            session()->setFlashdata('success', $mensaje);
        }

        return redirect()->to(route_to('servicios.show', $id));
    }

    private function pagina403(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(403)
            ->setBody(view('errors/html/error_403'));
    }
}
