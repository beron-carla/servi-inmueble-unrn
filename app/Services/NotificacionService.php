<?php

namespace App\Services;

use App\Repositories\NotificacionRepository;
use DateTimeImmutable;

class NotificacionService
{
    public function __construct(
        private ?NotificacionRepository $notificaciones = null,
    ) {
        $this->notificaciones ??= new NotificacionRepository();
    }

    public function registrarVencimientos(?string $hoy = null): int
    {
        $hoy = $hoy ?? date('Y-m-d');
        $config = config('Hito2');
        $dias = max(0, (int) $config->diasPorVencer);
        $hasta = (new DateTimeImmutable($hoy))->modify("+{$dias} days")->format('Y-m-d');
        $creadas = 0;

        foreach ($this->notificaciones->facturasPendientesConVencimiento($hoy) as $factura) {
            $vencimiento = (string) $factura['vencimiento_aplicable'];
            $tipo = null;

            if ($vencimiento < $hoy) {
                $tipo = 'vencida';
                $mensaje = 'La factura correspondiente se encuentra vencida';
            } elseif ($vencimiento <= $hasta) {
                $tipo = 'por_vencer';
                $mensaje = 'La factura correspondiente vence dentro de los próximos '
                    . $dias . ' días';
            }

            if ($tipo === null) {
                continue;
            }

            $idUsuario = (int) $factura['id_propietario'];
            $datos = [
                'id_usuario' => $idUsuario,
                'id_factura' => (int) $factura['id_factura'],
                'id_transaccion' => null,
                'tipo' => $tipo,
                'mensaje' => $mensaje,
                'idempotencia_key' => 'venc:' . (int) $factura['id_factura'] . ':' . $tipo,
            ];

            if ($this->notificaciones->insertarIdempotente($datos)) {
                $creadas++;
            }
        }

        return $creadas;
    }

    public function registrarPagoValidado(array $transaccion, array $factura): void
    {
        $this->registrarPago(
            $transaccion,
            $factura,
            'pago_validado',
            'Tu pago fue validado.',
        );
    }

    public function registrarPagoRechazado(array $transaccion, array $factura, string $motivo): void
    {
        $this->registrarPago(
            $transaccion,
            $factura,
            'pago_rechazado',
            'Tu pago fue rechazado. Motivo: ' . trim($motivo),
        );
    }

    public function listado(array $filtros, int $idUsuario, string $rol): array
    {
        $tipo = $this->normalizarTipo($filtros['tipo'] ?? null);
        $leida = $this->normalizarLeida($filtros['leida'] ?? null);
        $pagina = $this->normalizarPagina($filtros['page'] ?? null);
        $filtrosNormalizados = [
            'tipo' => $tipo,
            'leida' => $leida,
            'page' => $pagina,
        ];
        $idPropietario = $rol === 'admin' ? null : ($rol === 'propietario' ? $idUsuario : 0);
        $listado = $this->notificaciones->listar($filtrosNormalizados, $idPropietario);

        return [
            'notificaciones' => $listado['notificaciones'],
            'filtros' => $filtrosNormalizados,
            'paginacion' => [
                'pagina' => $listado['pagina'],
                'paginas' => $listado['paginas'],
                'total' => $listado['total'],
                'por_pagina' => $listado['por_pagina'],
            ],
        ];
    }

    public function cantidadNoLeidas(int $idUsuario): int
    {
        return $this->notificaciones->contarNoLeidas($idUsuario);
    }

    public function marcarLeida(int $idNotificacion, int $idUsuario, string $rol): array
    {
        if ($rol === 'admin') {
            return ['sin_permiso' => true, 'no_encontrado' => false];
        }

        $notificacion = $this->notificaciones->buscarPorId($idNotificacion);
        if ($notificacion === null) {
            return ['sin_permiso' => false, 'no_encontrado' => true];
        }
        if ((int) $notificacion['id_usuario'] !== $idUsuario) {
            return ['sin_permiso' => true, 'no_encontrado' => false];
        }

        $this->notificaciones->marcarLeida($idNotificacion, $idUsuario);

        return [
            'sin_permiso' => false,
            'no_encontrado' => false,
        ];
    }

    public function marcarTodasLeidas(int $idUsuario): int
    {
        return $this->notificaciones->marcarTodasLeidas($idUsuario);
    }

    private function registrarPago(array $transaccion, array $factura, string $tipo, string $mensaje): void
    {
        $idPropietario = (int) ($factura['id_propietario'] ?? 0);
        $idFactura = (int) ($factura['id_factura'] ?? 0);
        $idTransaccion = (int) ($transaccion['id_transaccion'] ?? 0);

        if ($idPropietario < 1 || $idFactura < 1 || $idTransaccion < 1) {
            return;
        }

        $this->notificaciones->insertarIdempotente([
            'id_usuario' => $idPropietario,
            'id_factura' => $idFactura,
            'id_transaccion' => $idTransaccion,
            'tipo' => $tipo,
            'mensaje' => mb_substr($mensaje, 0, 255),
            'idempotencia_key' => 'pago:' . $idTransaccion . ':' . $tipo,
        ]);
    }

    private function normalizarTipo(mixed $tipo): string
    {
        if (! is_string($tipo)) {
            return '';
        }
        $tipo = trim($tipo);

        return in_array($tipo, [
            'por_vencer',
            'vencida',
            'pago_validado',
            'pago_rechazado',
        ], true) ? $tipo : '';
    }

    private function normalizarLeida(mixed $leida): string
    {
        if ((! is_string($leida) && ! is_int($leida))
            || ! in_array((string) $leida, ['0', '1'], true)
        ) {
            return '';
        }

        return (string) $leida;
    }

    private function normalizarPagina(mixed $pagina): int
    {
        if ((! is_string($pagina) && ! is_int($pagina))
            || ! ctype_digit((string) $pagina)
            || (int) $pagina < 1
        ) {
            return 1;
        }

        return (int) $pagina;
    }
}
