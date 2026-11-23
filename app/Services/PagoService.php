<?php

namespace App\Services;

use App\Repositories\PagoRepository;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

final class PagoService
{
    private BaseConnection $db;
    private ?NumeroComprobanteService $numeros;

    public function __construct(
        private ?PagoRepository $repo = null,
        private ?ComprobanteService $comprobantes = null,
        private ?MoraService $mora = null,
        ?BaseConnection $db = null,
        ?NumeroComprobanteService $numeros = null,
    ) {
        $this->repo ??= new PagoRepository();
        $this->comprobantes ??= new ComprobanteService();
        $this->mora ??= new MoraService();
        $this->db = $db ?? db_connect();
        $this->numeros ??= $numeros ?? new NumeroComprobanteService($this->db);
    }

    public function opcionesPago(int $idFactura, int $idUsuario, string $rol): array
    {
        $factura = $this->repo->buscarFacturaParaPago($idFactura);

        if ($factura === null) {
            return $this->contexto(noEncontrado: true);
        }

        if (! $this->puedeAcceder($factura, $idUsuario, $rol)) {
            return $this->contexto(sinPermiso: true);
        }

        $estado = (new FacturaService())->estadoCalculado($factura);

        if ($factura['estado'] === 'pagada') {
            return $this->contexto(factura: $factura, mensaje: 'La factura ya está pagada');
        }

        if (! in_array($estado, ['pendiente', 'vencida'], true)) {
            return $this->contexto(factura: $factura, mensaje: 'La factura ya está pagada');
        }

        return $this->contexto(
            factura: $factura,
            estadoCalculado: $estado,
        );
    }

    public function registrar(int $idFactura, array $entrada, int $idUsuario, string $rol): array
    {
        $datos = $this->normalizarEntrada($entrada);

        if (! $this->db->transBegin()) {
            throw new RuntimeException('No se pudo iniciar el registro del pago.');
        }

        try {
            $factura = $this->repo->buscarFacturaParaPago($idFactura, true);

            if ($factura === null) {
                $this->db->transRollback();

                return $this->resultado(datos: $datos, noEncontrado: true);
            }

            if (! $this->puedeAcceder($factura, $idUsuario, $rol)) {
                $this->db->transRollback();

                return $this->resultado(datos: $datos, sinPermiso: true);
            }

            if ($factura['estado'] === 'pagada') {
                $this->db->transRollback();

                return $this->resultado(
                    datos: $datos,
                    errores: ['estado' => 'La factura ya está pagada'],
                );
            }

            $estado = (new FacturaService())->estadoCalculado($factura);
            if (! in_array($estado, ['pendiente', 'vencida'], true)) {
                $this->db->transRollback();

                return $this->resultado(
                    datos: $datos,
                    errores: ['estado' => 'La factura ya está pagada'],
                );
            }

            $errores = $this->validarDatos($datos, $factura, $entrada);

            if ($errores !== []) {
                $this->db->transRollback();

                return $this->resultado(datos: $datos, errores: $errores);
            }

            if ($this->repo->existePendiente($idFactura)) {
                $this->db->transRollback();

                return $this->resultado(
                    datos: $datos,
                    errores: [
                        'pendiente' => 'Ya existe un pago pendiente de validación para esta factura',
                    ],
                );
            }

            $comprobante = $this->comprobantes->validar(
                $this->archivoEntrada($entrada),
                $datos['medio_pago'],
            );
            $datos['numero_comprobante'] = $this->numeros->siguiente($datos['fecha_pago']);
            if (! preg_match('/^[A-Z]{2}-[0-9]{8}-[0-9]{6}$/', $datos['numero_comprobante'])) {
                throw new RuntimeException('No se pudo generar un número de comprobante válido.');
            }

            $datosPago = [
                'id_factura' => $idFactura,
                'id_propietario' => (int) $factura['id_propietario'],
                'fecha_pago' => $datos['fecha_pago'],
                'monto_abonado' => number_format((float) $datos['monto_abonado'], 2, '.', ''),
                'medio_pago' => $datos['medio_pago'],
                'numero_comprobante' => $datos['numero_comprobante'],
                'comprobante_digital' => $comprobante['contenido'] ?? null,
                'en_mora' => $this->mora->esPagoTardio($factura, $datos['fecha_pago']) ? 1 : 0,
                'estado' => 'pendiente',
                'comprobante_nombre' => $comprobante['nombre'] ?? null,
                'comprobante_mime' => $comprobante['mime'] ?? null,
            ];

            $idPago = $this->repo->crear($datosPago);

            if (! $this->db->transStatus() || ! $this->db->transCommit()) {
                throw new RuntimeException('No se pudo completar el registro del pago.');
            }

            return $this->resultado(datos: $datos, exito: true, idPago: $idPago);
        } catch (DatabaseException $exception) {
            $error = $this->db->error();
            $this->db->transRollback();

            if ((int) ($error['code'] ?? $exception->getCode()) === 1062) {
                return $this->resultado(
                    datos: $datos,
                    errores: [
                        'registro' => 'No se pudo registrar el pago porque ocurrió una colisión al generar el número de comprobante. Intente nuevamente.',
                    ],
                );
            }

            throw $exception;
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

    public function listadoPropio(array $filtros, int $idUsuario): array
    {
        $estado = in_array($filtros['estado'] ?? '', ['pendiente', 'validada', 'rechazada'], true)
            ? (string) $filtros['estado']
            : '';
        $desde = $this->fechaFiltro($filtros['desde'] ?? null);
        $hasta = $this->fechaFiltro($filtros['hasta'] ?? null);

        return $this->repo->listarPropio([
            'estado' => $estado,
            'desde' => $desde,
            'hasta' => $hasta,
        ], $idUsuario);
    }

    public function comprobante(int $idTransaccion, int $idUsuario, string $rol): array
    {
        $pago = $this->repo->buscarComprobante($idTransaccion);

        if ($pago === null) {
            return ['no_encontrado' => true, 'sin_permiso' => false, 'pago' => null];
        }

        if (! $this->puedeAccederTransaccion($pago, $idUsuario, $rol)) {
            return ['no_encontrado' => false, 'sin_permiso' => true, 'pago' => null];
        }

        if ($pago['comprobante_digital'] === null) {
            return ['no_encontrado' => true, 'sin_permiso' => false, 'pago' => null];
        }

        return ['no_encontrado' => false, 'sin_permiso' => false, 'pago' => $pago];
    }

    private function normalizarEntrada(array $entrada): array
    {
        $datos = [];
        foreach (['fecha_pago', 'monto_abonado', 'medio_pago'] as $campo) {
            $valor = $entrada[$campo] ?? '';
            $datos[$campo] = is_string($valor) || is_numeric($valor)
                ? trim((string) $valor)
                : '';
        }
        $datos['monto_abonado'] = str_replace(',', '.', $datos['monto_abonado']);

        return $datos;
    }

    private function validarDatos(
        array $datos,
        array $factura,
        array $entrada,
    ): array {
        $errores = [];

        $montoFactura = number_format((float) $factura['monto_total'], 2, '.', '');
        $montoPago = number_format((float) $datos['monto_abonado'], 2, '.', '');
        if ($datos['monto_abonado'] === '' || $montoPago !== $montoFactura) {
            $errores['monto_abonado'] = 'El monto debe ser igual al total de la factura';
        }

        if ($datos['fecha_pago'] === '' || ! $this->fechaValida($datos['fecha_pago'])
            || $datos['fecha_pago'] > date('Y-m-d')
        ) {
            $errores['fecha_pago'] = 'La fecha de pago no puede ser futura';
        }

        if (! in_array($datos['medio_pago'], [
            'transferencia',
            'debito automatico',
            'efectivo',
            'billetera virtual',
        ], true)) {
            $errores['medio_pago'] = 'El medio de pago es inválido';
        }

        $archivo = $this->archivoEntrada($entrada);
        if ($datos['medio_pago'] !== 'efectivo') {
            if ($archivo === null) {
                $errores['comprobante'] =
                    'El comprobante es obligatorio para este medio de pago';
            } elseif ((int) ($archivo['size'] ?? 0) > ComprobanteService::MAXIMO_BYTES) {
                $errores['comprobante'] = 'El comprobante no puede superar los 5 MB';
            } elseif ($this->comprobantes->validar($archivo, $datos['medio_pago']) === null) {
                $errores['comprobante'] = 'El comprobante debe ser PDF, JPG o JPEG';
            }
        } elseif ($archivo !== null) {
            if ((int) ($archivo['size'] ?? 0) > ComprobanteService::MAXIMO_BYTES) {
                $errores['comprobante'] = 'El comprobante no puede superar los 5 MB';
            } elseif ($this->comprobantes->validar($archivo, $datos['medio_pago']) === null) {
                $errores['comprobante'] = 'El comprobante debe ser PDF, JPG o JPEG';
            }
        }

        return $errores;
    }

    private function archivoEntrada(array $entrada): ?array
    {
        $archivo = $entrada['comprobante'] ?? null;

        if (! is_array($archivo)) {
            return null;
        }

        $tmp = (string) ($archivo['tmp_name'] ?? '');
        if (isset($archivo['contenido']) || $tmp !== '') {
            return $archivo;
        }

        return null;
    }

    private function puedeAcceder(array $factura, int $idUsuario, string $rol): bool
    {
        return $idUsuario > 0 && (
            $rol === 'admin'
            || ($rol === 'propietario'
                && (int) $factura['id_propietario'] === $idUsuario)
        );
    }

    private function puedeAccederTransaccion(array $pago, int $idUsuario, string $rol): bool
    {
        return $idUsuario > 0 && (
            $rol === 'admin'
            || ($rol === 'propietario'
                && (int) $pago['id_propietario'] === $idUsuario)
        );
    }

    private function fechaValida(string $fecha): bool
    {
        $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        $errores = DateTimeImmutable::getLastErrors();

        return $objeto !== false
            && ($errores === false
                || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))
            && $objeto->format('Y-m-d') === $fecha;
    }

    private function fechaFiltro(mixed $valor): string
    {
        if (! is_string($valor)) {
            return '';
        }
        $valor = trim($valor);

        return $this->fechaValida($valor) ? $valor : '';
    }

    private function contexto(
        ?array $factura = null,
        bool $sinPermiso = false,
        bool $noEncontrado = false,
        ?string $mensaje = null,
        ?string $estadoCalculado = null,
    ): array {
        return [
            'factura' => $factura,
            'sin_permiso' => $sinPermiso,
            'no_encontrado' => $noEncontrado,
            'mensaje' => $mensaje,
            'estado_calculado' => $estadoCalculado,
        ];
    }

    private function resultado(
        array $datos = [],
        array $errores = [],
        bool $exito = false,
        ?int $idPago = null,
        bool $sinPermiso = false,
        bool $noEncontrado = false,
    ): array {
        return [
            'datos' => $datos,
            'errores' => $errores,
            'exito' => $exito,
            'id_pago' => $idPago,
            'sin_permiso' => $sinPermiso,
            'no_encontrado' => $noEncontrado,
        ];
    }
}
