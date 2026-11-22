<?php

namespace App\Services;

use App\Repositories\MoraRepository;
use DateTimeImmutable;

final class MoraService
{
    private MoraRepository $mora;

    public function __construct(?MoraRepository $mora = null)
    {
        $this->mora = $mora ?? new MoraRepository();
    }

    public function vencimientoAplicable(array $factura): string
    {
        $segundo = trim((string) ($factura['fecha_segundo_vencimiento'] ?? ''));

        return $segundo !== ''
            ? $segundo
            : (string) ($factura['fecha_primer_vencimiento'] ?? '');
    }

    public function diasMora(string $vencimiento, ?string $hoy = null): int
    {
        $fechaHoy = $hoy !== null
            ? DateTimeImmutable::createFromFormat('!Y-m-d', $hoy)
            : new DateTimeImmutable('today');
        $fechaVencimiento = DateTimeImmutable::createFromFormat('!Y-m-d', $vencimiento);

        if ($fechaHoy === false || $fechaVencimiento === false) {
            return 0;
        }

        return max(0, (int) $fechaVencimiento->diff($fechaHoy)->format('%r%a'));
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array<string, mixed>
     */
    public function listado(array $filtros, int $idUsuario, string $rol): array
    {
        $propietarioAdmin = null;
        $opcionesPropietarios = $this->mora->opcionesPropietarios();

        if ($rol === 'admin') {
            $idPropietarioFiltro = $this->normalizarId(
                $filtros['propietario'] ?? null,
                $opcionesPropietarios,
                'id_usuario',
            );
            $propietarioAdmin = $idPropietarioFiltro > 0 ? $idPropietarioFiltro : null;
        } elseif ($rol === 'propietario') {
            $propietarioAdmin = max(0, $idUsuario);
        } else {
            $propietarioAdmin = 0;
        }

        $opcionesInmuebles = $this->mora->opcionesInmuebles($propietarioAdmin);
        $inmueble = $this->normalizarId(
            $filtros['inmueble'] ?? null,
            $opcionesInmuebles,
            'id_propiedad',
        );

        $desde = $this->normalizarFecha($filtros['desde'] ?? null);
        $hasta = $this->normalizarFecha($filtros['hasta'] ?? null);
        if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
            $desde = '';
            $hasta = '';
        }

        $diasMin = $this->normalizarDias($filtros['dias_min'] ?? null);
        $diasMax = $this->normalizarDias($filtros['dias_max'] ?? null);
        if ($diasMin !== null && $diasMax !== null && $diasMin > $diasMax) {
            $diasMin = null;
            $diasMax = null;
        }

        $pagina = $this->normalizarPagina($filtros['page'] ?? null);
        $normalizados = [
            'propietario' => $propietarioAdmin !== null && $rol === 'admin'
                ? (string) $propietarioAdmin
                : '',
            'inmueble' => $inmueble > 0 ? (string) $inmueble : '',
            'desde' => $desde,
            'hasta' => $hasta,
            'dias_min' => $diasMin === null ? '' : (string) $diasMin,
            'dias_max' => $diasMax === null ? '' : (string) $diasMax,
            'page' => (string) $pagina,
        ];

        $hoy = (new DateTimeImmutable('today'))->format('Y-m-d');
        $listado = $this->mora->listar($normalizados, $propietarioAdmin, $pagina, $hoy);

        return [
            'facturas' => $listado['facturas'],
            'metricas' => $this->mora->metricas($propietarioAdmin),
            'propietarios' => $opcionesPropietarios,
            'inmuebles' => $opcionesInmuebles,
            'filtros' => $normalizados,
            'paginacion' => [
                'pagina' => $listado['pagina'],
                'paginas' => $listado['paginas'],
                'total' => $listado['total'],
            ],
        ];
    }

    public function esPagoTardio(array $factura, string $fechaPago): bool
    {
        $vencimiento = $this->vencimientoAplicable($factura);

        if ($vencimiento === '' || $fechaPago === '') {
            return false;
        }

        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $fechaPago);
        $vence = DateTimeImmutable::createFromFormat('!Y-m-d', $vencimiento);

        return $fecha !== false
            && $vence !== false
            && $fecha->format('Y-m-d') > $vence->format('Y-m-d');
    }

    private function normalizarId(mixed $valor, array $opciones, string $campo): int
    {
        if (! is_numeric($valor)) {
            return 0;
        }
        $id = (int) $valor;
        foreach ($opciones as $opcion) {
            if ((int) ($opcion[$campo] ?? 0) === $id) {
                return $id;
            }
        }
        return 0;
    }

    private function normalizarFecha(mixed $valor): string
    {
        if (! is_string($valor) || $valor === '') {
            return '';
        }
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
        $errores = DateTimeImmutable::getLastErrors();
        if ($fecha === false
            || ($errores !== false
                && ($errores['warning_count'] !== 0 || $errores['error_count'] !== 0))
        ) {
            return '';
        }
        return $fecha->format('Y-m-d') === $valor ? $valor : '';
    }

    private function normalizarDias(mixed $valor): ?int
    {
        if (! is_string($valor) && ! is_numeric($valor)) {
            return null;
        }
        $texto = (string) $valor;
        if ($texto === '' || filter_var($texto, FILTER_VALIDATE_INT) === false) {
            return null;
        }
        $dias = (int) $texto;
        return $dias >= 0 ? $dias : null;
    }

    private function normalizarPagina(mixed $valor): int
    {
        if (! is_numeric($valor)) {
            return 1;
        }
        return max(1, (int) $valor);
    }
}
