<?php

namespace App\Services;

use App\Repositories\InformeRepository;

final class InformeService
{
    private const UNIDADES = ['kWh', 'm³', 'Gigas', 'Moneda fija'];

    public function __construct(
        private ?InformeRepository $repo = null,
    ) {
        $this->repo ??= new InformeRepository();
    }

    /**
     * @param array<string, mixed> $entrada
     * @return array<string, mixed>
     */
    public function consumoInteranual(array $entrada, int $idUsuario, string $rol): array
    {
        $rol = strtolower(trim($rol));
        if (! in_array($rol, ['admin', 'propietario'], true)) {
            return $this->resultadoSinPermiso();
        }

        $idPropietario = $rol === 'propietario' ? $idUsuario : null;
        $inmuebles = $this->repo->opcionesInmuebles($idPropietario);

        $idPropiedad = $this->normalizarId($entrada['inmueble'] ?? null, $inmuebles, 'id_propiedad');
        $anioActual = (int) date('Y');
        $anioBase = $this->normalizarAnio($entrada['anio_base'] ?? null, $anioActual);
        $anioComparado = $this->normalizarAnio(
            $entrada['anio_comparado'] ?? null,
            $anioActual - 1,
        );

        $baseResultado = [
            'sin_permiso' => false,
            'no_encontrado' => false,
            'inmueble_requerido' => false,
            'inmueble' => null,
            'inmuebles' => $inmuebles,
            'servicios' => [],
            'unidades' => self::UNIDADES,
            'filtros' => [
                'inmueble' => '',
                'servicio' => '',
                'anio_base' => (string) $anioBase,
                'anio_comparado' => (string) $anioComparado,
                'unidad' => '',
            ],
            'meses' => [],
            'anuales' => [],
            'hay_datos' => false,
            'etiqueta_valor' => 'Consumo',
            'es_monto' => false,
        ];

        if ($idPropiedad === 0) {
            if (($entrada['inmueble'] ?? '') !== '' && $entrada['inmueble'] !== null) {
                $propiedad = $this->repo->buscarPropiedad((int) $entrada['inmueble']);
                if ($propiedad !== null) {
                    if ($rol === 'propietario'
                        && (int) $propiedad['id_propietario'] !== $idUsuario
                    ) {
                        return $this->resultadoSinPermiso();
                    }
                } else {
                    $baseResultado['no_encontrado'] = true;

                    return $baseResultado;
                }
            }

            $baseResultado['inmueble_requerido'] = true;

            return $baseResultado;
        }

        $propiedad = $this->repo->buscarPropiedad($idPropiedad);
        if ($propiedad === null) {
            $baseResultado['no_encontrado'] = true;

            return $baseResultado;
        }
        if ($rol === 'propietario' && (int) $propiedad['id_propietario'] !== $idUsuario) {
            return $this->resultadoSinPermiso();
        }

        $servicios = $this->repo->opcionesServicios($idPropietario, $idPropiedad);
        $idServicio = $this->normalizarId(
            $entrada['servicio'] ?? null,
            $servicios,
            'id_propiedad_servicio',
        );
        $servicioSeleccionado = null;
        if ($idServicio > 0) {
            $servicioSeleccionado = $this->repo->buscarServicio($idServicio, $idPropiedad);
            if ($servicioSeleccionado === null) {
                $baseResultado['no_encontrado'] = true;

                return $baseResultado;
            }
        }

        $unidad = $this->normalizarUnidad(
            $entrada['unidad'] ?? null,
            $servicioSeleccionado,
        );
        $esMonto = $unidad === 'Moneda fija';
        $comparacion = $this->repo->compararMensual(
            $idPropiedad,
            $anioBase,
            $anioComparado,
            $idServicio > 0 ? $idServicio : null,
            $unidad !== '' ? $unidad : null,
        );

        $base = array_fill(1, 12, 0.0);
        $comparado = array_fill(1, 12, 0.0);
        foreach ($comparacion['filas'] as $fila) {
            $mes = (int) $fila['periodo_mes'];
            $valor = (float) $fila['valor'];
            if ((int) $fila['periodo_anio'] === $anioBase) {
                $base[$mes] = $valor;
            } elseif ((int) $fila['periodo_anio'] === $anioComparado) {
                $comparado[$mes] = $valor;
            }
        }

        $meses = [];
        $nombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $totalBase = 0.0;
        $totalComparado = 0.0;
        for ($mes = 1; $mes <= 12; $mes++) {
            $valorBase = $base[$mes];
            $valorComparado = $comparado[$mes];
            $diferencia = $valorComparado - $valorBase;
            $variacion = $valorBase == 0.0
                ? null
                : ($diferencia / $valorBase) * 100;
            $meses[] = [
                'mes' => $nombres[$mes],
                'base' => $valorBase,
                'comparado' => $valorComparado,
                'diferencia' => $diferencia,
                'variacion' => $variacion,
            ];
            $totalBase += $valorBase;
            $totalComparado += $valorComparado;
        }
        $diferenciaTotal = $totalComparado - $totalBase;
        $variacionTotal = $totalBase == 0.0
            ? null
            : ($diferenciaTotal / $totalBase) * 100;
        $meses[] = [
            'mes' => 'Total',
            'base' => $totalBase,
            'comparado' => $totalComparado,
            'diferencia' => $diferenciaTotal,
            'variacion' => $variacionTotal,
        ];

        $anuales = $this->repo->resumenAnual(
            $idPropiedad,
            $anioBase,
            $anioComparado,
            $idServicio > 0 ? $idServicio : null,
            $unidad !== '' ? $unidad : null,
        );

        return [
            'sin_permiso' => false,
            'no_encontrado' => false,
            'inmueble_requerido' => false,
            'inmueble' => $propiedad,
            'inmuebles' => $inmuebles,
            'servicios' => $servicios,
            'unidades' => self::UNIDADES,
            'filtros' => [
                'inmueble' => (string) $idPropiedad,
                'servicio' => $idServicio > 0 ? (string) $idServicio : '',
                'anio_base' => (string) $anioBase,
                'anio_comparado' => (string) $anioComparado,
                'unidad' => $unidad,
            ],
            'meses' => $meses,
            'anuales' => $anuales,
            'hay_datos' => $comparacion['hay_datos'],
            'etiqueta_valor' => $esMonto ? 'Monto' : 'Consumo',
            'es_monto' => $esMonto,
        ];
    }

    private function normalizarId(mixed $valor, array $opciones, string $campo): int
    {
        if (! is_string($valor) && ! is_int($valor)) {
            return 0;
        }
        $texto = trim((string) $valor);
        if ($texto === '' || ! ctype_digit($texto)) {
            return 0;
        }
        $id = (int) $texto;
        foreach ($opciones as $opcion) {
            if ((int) ($opcion[$campo] ?? 0) === $id) {
                return $id;
            }
        }
        return 0;
    }

    private function normalizarAnio(mixed $valor, int $predeterminado): int
    {
        if ((! is_string($valor) && ! is_int($valor))
            || ! ctype_digit((string) $valor)
        ) {
            return $predeterminado;
        }
        $anio = (int) $valor;

        return $anio >= 1901 && $anio <= 2155 ? $anio : $predeterminado;
    }

    private function normalizarUnidad(mixed $valor, ?array $servicio): string
    {
        if (is_string($valor) && in_array($valor, self::UNIDADES, true)) {
            return $valor;
        }
        if ($servicio !== null) {
            $unidad = (string) ($servicio['unidad_medida'] ?? '');
            if (in_array($unidad, self::UNIDADES, true)) {
                return $unidad;
            }
        }

        return '';
    }

    /**
     * @return array<string, mixed>
     */
    private function resultadoSinPermiso(): array
    {
        return [
            'sin_permiso' => true,
            'no_encontrado' => false,
            'inmueble_requerido' => false,
            'inmueble' => null,
            'inmuebles' => [],
            'servicios' => [],
            'unidades' => self::UNIDADES,
            'filtros' => [
                'inmueble' => '',
                'servicio' => '',
                'anio_base' => (string) date('Y'),
                'anio_comparado' => (string) ((int) date('Y') - 1),
                'unidad' => '',
            ],
            'meses' => [],
            'anuales' => [],
            'hay_datos' => false,
            'etiqueta_valor' => 'Consumo',
            'es_monto' => false,
        ];
    }
}
