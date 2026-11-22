<?php

namespace App\Controllers;

use App\Services\PagoService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

class PagosController extends BaseController
{
    public function __construct(private ?PagoService $pagos = null)
    {
        $this->pagos ??= new PagoService();
    }

    public function create(int $idFactura): string|ResponseInterface|RedirectResponse
    {
        $resultado = $this->pagos->opcionesPago(
            $idFactura,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }
        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound(
                'No se encontró la factura solicitada.',
            );
        }
        if ($resultado['mensaje'] !== null) {
            session()->setFlashdata('error', $resultado['mensaje']);

            return redirect()->to(route_to('facturas.index'));
        }

        return view('pagos/create', [
            'titulo' => 'Registrar pago',
            'factura' => $resultado['factura'],
            'estadoCalculado' => $resultado['estado_calculado'],
            'errores' => session()->getFlashdata('pago_errores') ?? [],
            'anterior' => session()->getFlashdata('pago_anterior') ?? [],
        ]);
    }

    public function store(int $idFactura): RedirectResponse|ResponseInterface
    {
        $entrada = $this->entradaFormulario();
        $resultado = $this->pagos->registrar(
            $idFactura,
            $entrada,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }
        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound(
                'No se encontró la factura solicitada.',
            );
        }
        if (! $resultado['exito']) {
            session()->setFlashdata('pago_errores', $resultado['errores']);
            session()->setFlashdata('pago_anterior', $resultado['datos']);
            session()->setFlashdata('error', reset($resultado['errores']));

            return redirect()->to(route_to('pagos.create', $idFactura));
        }

        session()->setFlashdata(
            'success',
            'Pago registrado. Queda pendiente de validación.',
        );

        return redirect()->to(route_to('pagos.index'));
    }

    public function index(): string
    {
        $entrada = $this->request->getGet() ?? [];
        $pagos = $this->pagos->listadoPropio(
            $entrada,
            (int) session('id_usuario'),
        );

        $filtros = [
            'estado' => in_array(
                (string) ($entrada['estado'] ?? ''),
                ['pendiente', 'validada', 'rechazada'],
                true,
            ) ? (string) $entrada['estado'] : '',
            'desde' => $this->fechaFiltro((string) ($entrada['desde'] ?? '')),
            'hasta' => $this->fechaFiltro((string) ($entrada['hasta'] ?? '')),
        ];

        return view('pagos/index', [
            'titulo' => 'Mis pagos',
            'pagos' => $pagos,
            'filtros' => $filtros,
        ]);
    }

    public function comprobante(int $idTransaccion): ResponseInterface
    {
        $resultado = $this->pagos->comprobante(
            $idTransaccion,
            (int) session('id_usuario'),
            (string) session('rol'),
        );

        if ($resultado['sin_permiso']) {
            return $this->pagina403();
        }
        if ($resultado['no_encontrado']) {
            throw PageNotFoundException::forPageNotFound(
                'No se encontró el pago solicitado.',
            );
        }

        $pago = $resultado['pago'];
        $nombre = preg_replace(
            '/[^A-Za-z0-9._-]/',
            '_',
            (string) ($pago['comprobante_nombre'] ?? 'comprobante'),
        ) ?: 'comprobante';

        return $this->response
            ->setHeader('Content-Type', (string) $pago['comprobante_mime'])
            ->setHeader('Content-Disposition', 'inline; filename="' . $nombre . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((string) $pago['comprobante_digital']);
    }

    private function entradaFormulario(): array
    {
        $entrada = [];
        foreach (['fecha_pago', 'monto_abonado', 'medio_pago', 'numero_comprobante'] as $campo) {
            $valor = $this->request->getPost($campo);
            $entrada[$campo] = is_string($valor) || is_numeric($valor)
                ? (string) $valor
                : '';
        }

        $archivo = $this->request->getFile('comprobante');
        if ($archivo !== null && $archivo->isValid()) {
            $entrada['comprobante'] = [
                'tmp_name' => $archivo->getTempName(),
                'name' => $archivo->getName(),
                'size' => $archivo->getSize(),
            ];
        }

        return $entrada;
    }

    private function fechaFiltro(string $fecha): string
    {
        if ($fecha === '') {
            return '';
        }
        $objeto = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        $errores = \DateTimeImmutable::getLastErrors();

        return $objeto !== false
            && ($errores === false
                || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))
            && $objeto->format('Y-m-d') === $fecha
            ? $fecha
            : '';
    }

    private function pagina403(): ResponseInterface
    {
        return $this->response
            ->setStatusCode(403)
            ->setBody(view('errors/html/error_403'));
    }
}
