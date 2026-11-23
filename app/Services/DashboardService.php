<?php

namespace App\Services;

use App\Repositories\DashboardRepository;

final class DashboardService
{
    public function __construct(
        private ?DashboardRepository $repo = null,
    ) {
        $this->repo ??= new DashboardRepository();
    }

    /**
     * @return array{tarjetas: array, serie_mensual: array, distribucion_estados: array, enlaces: array}
     */
    public function metricas(int $idUsuario, string $rol): array
    {
        $rol = strtolower(trim($rol));
        $idPropietario = $rol === 'propietario'
            ? $idUsuario
            : ($rol === 'admin' ? null : 0);
        $hoy = date('Y-m-d');
        $anio = (int) date('Y');
        $desdeMes = date('Y-m-01');

        $estados = $this->repo->facturasPorEstadoCalculado($idPropietario, $anio);
        $pendientes = $estados['pendiente'];
        $vencidas = $estados['vencida'];
        $pagadas = $estados['pagada'];

        $tarjetas = [
            'inmuebles_activos' => $this->repo->inmueblesActivos($idPropietario),
            'facturas_pendientes' => $pendientes,
            'facturas_vencidas' => $vencidas,
            'pagos_pendientes' => [
                'cantidad' => $this->repo->pagosPendientes($idPropietario),
            ],
            'recaudacion_mes' => [
                'monto' => $this->repo->recaudacionMes($idPropietario, $desdeMes, $hoy),
            ],
        ];

        if ($rol === 'admin') {
            $tarjetas['usuarios_activos'] = $this->repo->usuariosActivos();
        } else {
            $tarjetas['notificaciones_no_leidas'] = [
                'cantidad' => $this->repo->noLeidas($idUsuario),
            ];
        }

        $desdeSerie = date('Y-m-01', strtotime('-11 months'));
        $hastaSerie = date('Y-m-t');

        $distribucion = [
            'pendiente' => $pendientes['cantidad'],
            'vencida' => $vencidas['cantidad'],
            'pagada' => $pagadas['cantidad'],
        ];

        $enlaces = [
            'facturas_pendientes' => '/facturas?estado=pendiente',
            'facturas_vencidas' => '/facturas?estado=vencida',
            'pagos_pendientes' => $rol === 'admin'
                ? '/admin/pagos?estado=pendiente'
                : '/pagos?estado=pendiente',
            'mora' => '/mora',
            'notificaciones' => '/notificaciones?leida=0',
        ];

        return [
            'tarjetas' => $tarjetas,
            'serie_mensual' => $this->repo->serieMensual($idPropietario, $desdeSerie, $hastaSerie),
            'distribucion_estados' => $distribucion,
            'enlaces' => $enlaces,
        ];
    }
}
