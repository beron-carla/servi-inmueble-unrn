<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\TipoServicioService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Exceptions\PageNotFoundException;

class TiposServicioController extends BaseController
{
    public function __construct(private ?TipoServicioService $tipos = null)
    {
        $this->tipos ??= new TipoServicioService();
    }

    public function index(): string
    {
        $resultado = $this->tipos->listarParaAdministracion([
            'q' => $this->request->getGet('q'),
            'categoria' => $this->request->getGet('categoria'),
            'activo' => $this->request->getGet('activo'),
            'page' => $this->request->getGet('page'),
        ]);
        $resultado['pager']?->only(['q', 'categoria', 'activo']);

        return view('admin/tipos-servicio/index', ['titulo' => 'Tipos de servicio'] + $resultado);
    }

    public function show(int $id): string
    {
        $tipo = $this->tipos->buscarPorId($id);

        if ($tipo === null) {
            throw PageNotFoundException::forPageNotFound('No se encontró el tipo de servicio solicitado.');
        }

        return view('admin/tipos-servicio/show', [
            'titulo' => 'Detalle del tipo de servicio',
            'tipo' => $tipo,
        ]);
    }

    public function create(): string
    {
        return view('admin/tipos-servicio/create', [
            'titulo' => 'Alta de tipo de servicio',
            'opciones' => $this->tipos->opcionesFormulario(),
            'errores' => session()->getFlashdata('tipo_servicio_errores') ?? [],
            'anterior' => session()->getFlashdata('tipo_servicio_anterior') ?? [],
        ]);
    }

    public function edit(int $id): string
    {
        $tipo = $this->tipos->buscarPorId($id);

        if ($tipo === null) {
            throw PageNotFoundException::forPageNotFound('No se encontró el tipo de servicio solicitado.');
        }

        return view('admin/tipos-servicio/edit', [
            'titulo' => 'Modificar tipo de servicio',
            'tipo' => $tipo,
            'opciones' => $this->tipos->opcionesFormulario(),
            'errores' => session()->getFlashdata('tipo_servicio_errores') ?? [],
            'anterior' => session()->getFlashdata('tipo_servicio_anterior') ?? [],
        ]);
    }

    public function store(): RedirectResponse
    {
        $tipo = $this->postString('tipo');
        $categoria = $this->postString('categoria');
        $unidadMedida = $this->postString('unidad_medida');
        $proveedor = $this->postString('proveedor_predeterminado');

        $resultado = $this->tipos->crear($tipo, $categoria, $unidadMedida, $proveedor);

        if (! $resultado['exito']) {
            session()->setFlashdata('tipo_servicio_errores', $resultado['errores']);
            session()->setFlashdata('tipo_servicio_anterior', [
                'tipo' => $tipo,
                'categoria' => $categoria,
                'unidad_medida' => $unidadMedida,
                'proveedor_predeterminado' => $proveedor,
            ]);
            session()->setFlashdata('error', reset($resultado['errores']));

            return redirect()->to(route_to('admin.tipos.create'));
        }

        session()->setFlashdata('success', 'Tipo de servicio creado correctamente.');

        return redirect()->to(route_to('admin.tipos.create'));
    }

    public function update(int $id): RedirectResponse
    {
        if ($this->tipos->buscarPorId($id) === null) {
            throw PageNotFoundException::forPageNotFound('No se encontró el tipo de servicio solicitado.');
        }

        $datos = [
            'tipo' => $this->postString('tipo'),
            'categoria' => $this->postString('categoria'),
            'unidad_medida' => $this->postString('unidad_medida'),
            'proveedor_predeterminado' => $this->postString('proveedor_predeterminado'),
            'activo' => $this->postString('activo'),
        ];
        $resultado = $this->tipos->actualizar(
            $id,
            $datos['tipo'],
            $datos['categoria'],
            $datos['unidad_medida'],
            $datos['proveedor_predeterminado'],
            $datos['activo'],
        );

        if (! $resultado['exito']) {
            session()->setFlashdata('tipo_servicio_errores', $resultado['errores']);
            session()->setFlashdata('tipo_servicio_anterior', $datos);
            session()->setFlashdata('error', reset($resultado['errores']));

            return redirect()->to(route_to('admin.tipos.edit', $id));
        }

        session()->setFlashdata('success', 'Tipo de servicio modificado correctamente.');

        return redirect()->to(route_to('admin.tipos.index'));
    }

    public function deactivate(int $id): RedirectResponse
    {
        return $this->cambiarEstado($id, '0', 'Tipo de servicio desactivado correctamente.');
    }

    public function activate(int $id): RedirectResponse
    {
        return $this->cambiarEstado($id, '1', 'Tipo de servicio reactivado correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        if ($this->tipos->buscarPorId($id) === null) {
            throw PageNotFoundException::forPageNotFound('No se encontró el tipo de servicio solicitado.');
        }

        session()->setFlashdata(
            'warning',
            'La eliminación se habilitará al completar la integración con HU-13 y HU-14.',
        );

        return redirect()->to(route_to('admin.tipos.show', $id));
    }

    private function cambiarEstado(int $id, string $activo, string $mensajeExito): RedirectResponse
    {
        if ($this->tipos->buscarPorId($id) === null) {
            throw PageNotFoundException::forPageNotFound('No se encontró el tipo de servicio solicitado.');
        }

        $resultado = $this->tipos->cambiarEstado($id, $activo);

        if (! $resultado['exito']) {
            session()->setFlashdata('error', reset($resultado['errores']));
        } else {
            session()->setFlashdata('success', $mensajeExito);
        }

        return redirect()->to(route_to('admin.tipos.index'));
    }

    private function postString(string $campo): string
    {
        $valor = $this->request->getPost($campo);

        return is_string($valor) ? $valor : '';
    }
}
