<?php

namespace App\Controllers;

use App\Services\FacturaService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class FacturasController extends BaseController
{
    public function __construct(private ?FacturaService $facturas = null)
    {
        $this->facturas ??= new FacturaService();
    }

    public function index(): string
    {
        return view('facturas/index', [
            'titulo' => 'Facturas',
            ...$this->facturas->listado(
                $this->request->getGet() ?? [],
                (int) session('id_usuario'),
                (string) session('rol'),
            ),
        ]);
    }

    public function create(int $idServicio): string|ResponseInterface
    {
        $resultado = $this->facturas->opcionesAlta(
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

        if ($resultado['mensaje'] !== null) {
            session()->setFlashdata('error', $resultado['mensaje']);

            return redirect()->to(route_to('servicios.show', $idServicio));
        }

        return view('facturas/create', [
            'titulo' => 'Registrar factura',
            'servicio' => $resultado['servicio'],
            'errores' => session()->getFlashdata('factura_errores') ?? [],
            'anterior' => session()->getFlashdata('factura_anterior') ?? [],
        ]);
    }

    public function store(int $idServicio): RedirectResponse|ResponseInterface
    {
        $resultado = $this->facturas->crear(
            $this->entradaFormulario(),
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

        if ($resultado['mensaje'] !== null) {
            session()->setFlashdata('error', $resultado['mensaje']);

            return redirect()->to(route_to('servicios.show', $idServicio));
        }

        if (! $resultado['exito']) {
            session()->setFlashdata('factura_errores', $resultado['errores']);
            session()->setFlashdata('factura_anterior', $resultado['datos']);
            session()->setFlashdata('error', reset($resultado['errores']));

            return redirect()->to(route_to('facturas.create', $idServicio));
        }

        $estado = match ($resultado['estado_calculado']) {
            'vencida' => 'Vencida',
            'pagada' => 'Pagada',
            default => 'Pendiente',
        };
        session()->setFlashdata('success', 'Factura registrada correctamente. Estado calculado: ' . $estado . '.');

        return redirect()->to(route_to('servicios.show', $idServicio));
    }

    /**
     * @return array<string, string>
     */
    private function entradaFormulario(): array
    {
        $entrada = [];

        foreach ([
            'periodo',
            'periodo_mes',
            'periodo_anio',
            'fecha_emision',
            'fecha_primer_vencimiento',
            'fecha_segundo_vencimiento',
            'monto_total',
            'consumo_del_periodo',
            'lectura_anterior',
            'lectura_actual',
        ] as $campo) {
            $valor = $this->request->getPost($campo);
            $entrada[$campo] = is_string($valor) || is_numeric($valor) ? (string) $valor : '';
        }

        return $entrada;
    }

    private function pagina403(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(403)
            ->setBody(view('errors/html/error_403'));
    }
}
