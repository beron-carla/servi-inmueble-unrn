<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\TipoServicioService;
use CodeIgniter\HTTP\RedirectResponse;

class TiposServicioController extends BaseController
{
    public function __construct(private ?TipoServicioService $tipos = null)
    {
        $this->tipos ??= new TipoServicioService();
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

    private function postString(string $campo): string
    {
        $valor = $this->request->getPost($campo);

        return is_string($valor) ? $valor : '';
    }
}
