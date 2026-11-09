<?php

namespace App\Controllers;

use App\Services\InmuebleService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class InmueblesController extends BaseController
{
    public function __construct(private ?InmuebleService $inmuebles = null)
    {
        $this->inmuebles ??= new InmuebleService();
    }

    public function index(): string|ResponseInterface
    {
        $rol = (string) session('rol');
        $resultado = $this->inmuebles->listar(
            $this->getFilters(),
            (int) session('id_usuario'),
            $rol,
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }

        $resultado['pager']?->only(['ciudad', 'tipo', 'estado', 'q']);

        return view('inmuebles/index', [
            'titulo' => $rol === 'admin' ? 'Inmuebles' : 'Mis inmuebles',
            'esAdmin' => $rol === 'admin',
        ] + $resultado);
    }

    public function create(): string
    {
        return view('inmuebles/create', [
            'titulo' => 'Alta de inmueble',
            'esAdmin' => session('rol') === 'admin',
            'opciones' => $this->inmuebles->opcionesFormulario((string) session('rol')),
            'errores' => session()->getFlashdata('inmueble_errores') ?? [],
            'anterior' => session()->getFlashdata('inmueble_anterior') ?? [],
        ]);
    }

    public function store(): RedirectResponse|ResponseInterface
    {
        $resultado = $this->inmuebles->crear(
            $this->entradaFormulario(),
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if (isset($resultado['errores']['general'])) {
            return $this->pagina403();
        }

        if (! $resultado['exito']) {
            session()->setFlashdata('inmueble_errores', $resultado['errores']);
            session()->setFlashdata('inmueble_anterior', $resultado['datos']);
            session()->setFlashdata('error', reset($resultado['errores']));

            return redirect()->to(route_to('inmuebles.create'));
        }

        session()->setFlashdata('success', 'Inmueble creado correctamente.');

        return redirect()->to(route_to('inmuebles.index'));
    }

    public function edit(int $id): string|ResponseInterface
    {
        $resultado = $this->inmuebles->detalle(
            $id,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }

        if ($resultado['inmueble'] === null) {
            throw PageNotFoundException::forPageNotFound('No se encontró el inmueble solicitado.');
        }

        return view('inmuebles/edit', [
            'titulo' => 'Modificar inmueble',
            'esAdmin' => session('rol') === 'admin',
            'inmueble' => $resultado['inmueble'],
            'opciones' => $this->inmuebles->opcionesFormulario((string) session('rol')),
            'errores' => session()->getFlashdata('inmueble_errores') ?? [],
            'anterior' => session()->getFlashdata('inmueble_anterior') ?? [],
        ]);
    }

    public function update(int $id): RedirectResponse|ResponseInterface
    {
        $resultado = $this->inmuebles->actualizar(
            $id,
            $this->entradaFormulario(),
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }

        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound('No se encontró el inmueble solicitado.');
        }

        if (! $resultado['exito']) {
            session()->setFlashdata('inmueble_errores', $resultado['errores']);
            session()->setFlashdata('inmueble_anterior', $resultado['datos']);
            session()->setFlashdata('error', reset($resultado['errores']));

            return redirect()->to(route_to('inmuebles.edit', $id));
        }

        session()->setFlashdata('success', 'Inmueble actualizado correctamente.');

        return redirect()->to(route_to('inmuebles.show', $id));
    }

    public function deactivate(int $id): RedirectResponse|ResponseInterface
    {
        return $this->cambiarEstado($id, false);
    }

    public function activate(int $id): RedirectResponse|ResponseInterface
    {
        return $this->cambiarEstado($id, true);
    }

    public function show(int $id): string|ResponseInterface
    {
        $resultado = $this->inmuebles->detalle(
            $id,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }

        if ($resultado['inmueble'] === null) {
            throw PageNotFoundException::forPageNotFound('No se encontró el inmueble solicitado.');
        }

        return view('inmuebles/show', [
            'titulo' => 'Detalle del inmueble',
            'inmueble' => $resultado['inmueble'],
            'servicios' => $resultado['servicios'],
            'esAdmin' => session('rol') === 'admin',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getFilters(): array
    {
        return [
            'ciudad' => $this->request->getGet('ciudad'),
            'tipo' => $this->request->getGet('tipo'),
            'estado' => $this->request->getGet('estado'),
            'q' => $this->request->getGet('q'),
            'page' => $this->request->getGet('page'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function entradaFormulario(): array
    {
        $entrada = [];

        foreach ([
            'alias',
            'calle',
            'numero',
            'piso',
            'depto',
            'ciudad',
            'id_tipo_inmueble',
            'superficie',
            'estado',
            'id_propietario',
        ] as $campo) {
            $valor = $this->request->getPost($campo);
            $entrada[$campo] = is_string($valor) || is_numeric($valor) ? (string) $valor : '';
        }

        return $entrada;
    }

    private function cambiarEstado(int $id, bool $reactivar): RedirectResponse|ResponseInterface
    {
        $resultado = $reactivar
            ? $this->inmuebles->reactivar($id, (int) session('id_usuario'), (string) session('rol'))
            : $this->inmuebles->darDeBaja($id, (int) session('id_usuario'), (string) session('rol'));

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }

        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound('No se encontró el inmueble solicitado.');
        }

        if (! $resultado['exito']) {
            session()->setFlashdata('error', reset($resultado['errores']));

            return redirect()->to(route_to('inmuebles.show', $id));
        }

        session()->setFlashdata(
            'success',
            $reactivar ? 'Inmueble reactivado correctamente.' : 'Inmueble dado de baja correctamente.',
        );

        return redirect()->to(route_to('inmuebles.show', $id));
    }

    private function pagina403(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(403)
            ->setBody(view('errors/html/error_403'));
    }
}
