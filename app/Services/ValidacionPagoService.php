<?php

namespace App\Services;

use App\Repositories\PagoRepository;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

final class ValidacionPagoService
{
    private BaseConnection $db;

    public function __construct(
        private ?PagoRepository $repo = null,
        private ?NotificacionService $notificaciones = null,
        ?BaseConnection $db = null,
    ) {
        $this->repo ??= new PagoRepository();
        $this->notificaciones ??= new NotificacionService();
        $this->db = $db ?? db_connect();
    }

    public function listado(array $entrada, int $idUsuario, string $rol): array
    {
        if ($rol !== 'admin') {
            return ['sin_permiso' => true, 'pagos' => [], 'filtros' => [], 'propietarios' => []];
        }

        $filtros = [
            'estado' => in_array((string) ($entrada['estado'] ?? ''), ['pendiente', 'validada', 'rechazada'], true)
                ? (string) $entrada['estado']
                : '',
            'propietario' => ctype_digit((string) ($entrada['propietario'] ?? ''))
                ? (string) $entrada['propietario']
                : '',
            'factura' => ctype_digit((string) ($entrada['factura'] ?? ''))
                ? (string) $entrada['factura']
                : '',
            'medio_pago' => in_array((string) ($entrada['medio_pago'] ?? ''), [
                'transferencia',
                'debito automatico',
                'efectivo',
                'billetera virtual',
            ], true) ? (string) $entrada['medio_pago'] : '',
            'desde' => $this->fechaFiltro($entrada['desde'] ?? null),
            'hasta' => $this->fechaFiltro($entrada['hasta'] ?? null),
        ];

        return [
            'sin_permiso' => false,
            'pagos' => $this->repo->listarAdmin($filtros),
            'filtros' => $filtros,
            'propietarios' => $this->repo->propietariosConPagos(),
        ];
    }

    public function detalle(int $idTransaccion, int $idUsuario, string $rol): array
    {
        if ($rol !== 'admin') {
            return ['sin_permiso' => true, 'no_encontrado' => false, 'pago' => null];
        }

        $pago = $this->repo->buscarParaValidacion($idTransaccion);
        if ($pago === null) {
            return ['sin_permiso' => false, 'no_encontrado' => true, 'pago' => null];
        }

        return ['sin_permiso' => false, 'no_encontrado' => false, 'pago' => $pago];
    }

    public function validar(int $idTransaccion, int $idAdmin, string $rol = 'admin'): array
    {
        if ($rol !== 'admin') {
            return ['sin_permiso' => true, 'no_encontrado' => false, 'ya_procesado' => false];
        }

        if (! $this->db->transBegin()) {
            throw new RuntimeException('No se pudo iniciar la validación del pago.');
        }

        try {
            $pago = $this->repo->buscarParaValidacion($idTransaccion, true);
            if ($pago === null) {
                $this->db->transRollback();
                return ['sin_permiso' => false, 'no_encontrado' => true, 'ya_procesado' => false];
            }
            if ((string) $pago['estado'] !== 'pendiente') {
                $this->db->transRollback();
                return ['sin_permiso' => false, 'no_encontrado' => false, 'ya_procesado' => true];
            }

            $fechaValidacion = date('Y-m-d H:i:s');
            if (! $this->repo->marcarValidada($idTransaccion, $idAdmin, $fechaValidacion)
                || ! $this->repo->marcarFacturaPagada((int) $pago['id_factura'])
            ) {
                throw new RuntimeException('No se pudo validar el pago.');
            }

            $pago['estado'] = 'validada';
            $pago['fecha_validacion'] = $fechaValidacion;
            $pago['id_admin_validador'] = $idAdmin;
            $this->notificaciones->registrarPagoValidado($pago, $pago);

            if (! $this->db->transStatus() || ! $this->db->transCommit()) {
                throw new RuntimeException('No se pudo completar la validación del pago.');
            }

            return ['sin_permiso' => false, 'no_encontrado' => false, 'ya_procesado' => false];
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

    public function rechazar(int $idTransaccion, int $idAdmin, string $motivo, string $rol = 'admin'): array
    {
        if ($rol !== 'admin') {
            return [
                'sin_permiso' => true,
                'no_encontrado' => false,
                'ya_procesado' => false,
                'errores' => [],
            ];
        }

        $motivo = trim($motivo);
        if ($motivo === '') {
            return [
                'sin_permiso' => false,
                'no_encontrado' => false,
                'ya_procesado' => false,
                'errores' => ['motivo' => 'El motivo del rechazo es obligatorio'],
            ];
        }
        if (mb_strlen($motivo) > 255) {
            return [
                'sin_permiso' => false,
                'no_encontrado' => false,
                'ya_procesado' => false,
                'errores' => ['motivo' => 'El motivo del rechazo no puede superar los 255 caracteres'],
            ];
        }

        if (! $this->db->transBegin()) {
            throw new RuntimeException('No se pudo iniciar el rechazo del pago.');
        }

        try {
            $pago = $this->repo->buscarParaValidacion($idTransaccion, true);
            if ($pago === null) {
                $this->db->transRollback();
                return [
                    'sin_permiso' => false,
                    'no_encontrado' => true,
                    'ya_procesado' => false,
                    'errores' => [],
                ];
            }
            if ((string) $pago['estado'] !== 'pendiente') {
                $this->db->transRollback();
                return [
                    'sin_permiso' => false,
                    'no_encontrado' => false,
                    'ya_procesado' => true,
                    'errores' => [],
                ];
            }

            $fechaValidacion = date('Y-m-d H:i:s');
            if (! $this->repo->marcarRechazada($idTransaccion, $idAdmin, $motivo, $fechaValidacion)) {
                throw new RuntimeException('No se pudo rechazar el pago.');
            }

            $pago['estado'] = 'rechazada';
            $pago['fecha_validacion'] = $fechaValidacion;
            $pago['motivo_rechazo'] = $motivo;
            $pago['id_admin_validador'] = $idAdmin;
            $this->notificaciones->registrarPagoRechazado($pago, $pago, $motivo);

            if (! $this->db->transStatus() || ! $this->db->transCommit()) {
                throw new RuntimeException('No se pudo completar el rechazo del pago.');
            }

            return [
                'sin_permiso' => false,
                'no_encontrado' => false,
                'ya_procesado' => false,
                'errores' => [],
            ];
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

    private function fechaFiltro(mixed $valor): string
    {
        if (! is_string($valor)) {
            return '';
        }
        $valor = trim($valor);
        $objeto = \DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        $errores = \DateTimeImmutable::getLastErrors();

        return $objeto !== false
            && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))
            && $objeto->format('Y-m-d') === $valor
            ? $valor
            : '';
    }
}
