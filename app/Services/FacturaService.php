<?php

namespace App\Services;

use App\Repositories\FacturaRepository;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

class FacturaService
{
    private BaseConnection $db;

    public function __construct(
        private ?FacturaRepository $facturas = null,
        ?BaseConnection $db = null,
    ) {
        $this->facturas ??= new FacturaRepository();
        $this->db = $db ?? db_connect();
    }

    /**
     * @param array<string, mixed> $entrada
     * @return array<string, mixed>
     */
    public function listado(array $entrada, int $idUsuario, string $rol): array
    {
        $idPropietario = match ($rol) {
            'admin' => null,
            'propietario' => max(0, $idUsuario),
            default => 0,
        };
        $propiedades = $this->facturas->opcionesPropiedades($idPropietario);
        $servicios = $this->facturas->opcionesServicios($idPropietario);
        $filtros = [
            'inmueble' => $this->normalizarIdFiltro(
                $entrada['inmueble'] ?? null,
                $propiedades,
                'id_propiedad',
            ),
            'servicio' => $this->normalizarIdFiltro(
                $entrada['servicio'] ?? null,
                $servicios,
                'id_propiedad_servicio',
            ),
            'estado' => $this->normalizarFiltroTexto(
                $entrada['estado'] ?? null,
                ['pendiente', 'pagada', 'vencida'],
            ),
            'desde' => $this->normalizarFechaFiltro($entrada['desde'] ?? null),
            'hasta' => $this->normalizarFechaFiltro($entrada['hasta'] ?? null),
        ];

        if ($filtros['desde'] !== '' && $filtros['hasta'] !== ''
            && $filtros['desde'] > $filtros['hasta']
        ) {
            $filtros['desde'] = '';
            $filtros['hasta'] = '';
        }

        $pagina = $this->normalizarPagina($entrada['page'] ?? null);
        $listado = $this->facturas->listar(
            $filtros,
            $idPropietario,
            $pagina,
            date('Y-m-d'),
        );

        return [
            'facturas' => $listado['facturas'],
            'resumen' => $listado['resumen'],
            'propiedades' => $propiedades,
            'servicios' => $servicios,
            'filtros' => $filtros,
            'paginacion' => [
                'pagina' => $listado['pagina'],
                'paginas' => $listado['paginas'],
                'total' => $listado['total'],
                'por_pagina' => $listado['por_pagina'],
            ],
        ];
    }

    /**
     * @return array{
     *     servicio: array<string, mixed>|null,
     *     sin_permiso: bool,
     *     no_encontrado: bool,
     *     mensaje: string|null
     * }
     */
    public function opcionesAlta(int $idServicio, int $idUsuario, string $rol): array
    {
        $servicio = $this->facturas->buscarServicioParaAlta($idServicio);

        if ($servicio === null) {
            return $this->resultadoContexto(noEncontrado: true);
        }

        if (! $this->puedeAcceder($servicio, $idUsuario, $rol)) {
            return $this->resultadoContexto(sinPermiso: true);
        }

        return $this->resultadoContexto(
            servicio: $servicio,
            mensaje: $this->mensajeServicioNoElegible($servicio),
        );
    }

    /**
     * @param array<string, mixed> $entrada
     * @return array<string, mixed>
     */
    public function crear(array $entrada, int $idServicio, int $idUsuario, string $rol): array
    {
        $datos = $this->normalizarDatos($entrada);

        if (! $this->db->transBegin()) {
            throw new RuntimeException('No se pudo iniciar el registro de la factura.');
        }

        try {
            $servicio = $this->facturas->buscarServicioParaAlta($idServicio, true);

            if ($servicio === null) {
                $this->db->transRollback();

                return $this->resultadoOperacion($datos, noEncontrado: true);
            }

            if (! $this->puedeAcceder($servicio, $idUsuario, $rol)) {
                $this->db->transRollback();

                return $this->resultadoOperacion($datos, sinPermiso: true);
            }

            $mensajeServicio = $this->mensajeServicioNoElegible($servicio);

            if ($mensajeServicio !== null) {
                $this->db->transRollback();

                return $this->resultadoOperacion($datos, mensaje: $mensajeServicio);
            }

            $errores = $this->validarDatos($datos, $servicio);

            if ($errores !== []) {
                $this->db->transRollback();

                return $this->resultadoOperacion($datos, errores: $errores);
            }

            if ($this->facturas->existePeriodo(
                $idServicio,
                $datos['periodo'],
                (int) $datos['periodo_anio'],
                $datos['periodo_mes'],
            )) {
                $this->db->transRollback();

                return $this->resultadoOperacion(
                    $datos,
                    errores: ['periodo' => 'Ya existe una factura para ese período'],
                );
            }

            $datosFactura = [
                'id_propiedad_servicio' => $idServicio,
                'periodo' => $datos['periodo'],
                'periodo_mes' => $datos['periodo_mes'],
                'periodo_anio' => (int) $datos['periodo_anio'],
                'fecha_emision' => $datos['fecha_emision'],
                'fecha_primer_vencimiento' => $datos['fecha_primer_vencimiento'],
                'fecha_segundo_vencimiento' => $datos['fecha_segundo_vencimiento'] ?: null,
                'monto_total' => $datos['monto_total'],
                'consumo_del_periodo' => (int) $datos['consumo_del_periodo'],
                'lectura_anterior' => $datos['lectura_anterior'] === ''
                    ? null
                    : (int) $datos['lectura_anterior'],
                'lectura_actual' => $datos['lectura_actual'] === ''
                    ? null
                    : (int) $datos['lectura_actual'],
                'unidad_medida' => $servicio['unidad_medida'],
                'proveedor' => $servicio['proveedor_predeterminado'],
                'categoria_factura' => $servicio['categoria'],
                'estado' => 'pendiente',
            ];
            $idFactura = $this->facturas->crear($datosFactura);

            if ($idFactura === false || $idFactura < 1) {
                $error = $this->db->error();

                if ((int) ($error['code'] ?? 0) === 1062) {
                    $this->db->transRollback();

                    return $this->resultadoOperacion(
                        $datos,
                        errores: ['periodo' => 'Ya existe una factura para ese período'],
                    );
                }

                throw new RuntimeException('No se pudo guardar la factura.');
            }

            if (! $this->db->transStatus() || ! $this->db->transCommit()) {
                throw new RuntimeException('No se pudo completar el registro de la factura.');
            }

            return $this->resultadoOperacion(
                $datos,
                exito: true,
                idFactura: $idFactura,
                estadoCalculado: $this->estadoCalculado($datosFactura),
            );
        } catch (DatabaseException $exception) {
            $error = $this->db->error();
            $this->db->transRollback();

            if ((int) ($error['code'] ?? $exception->getCode()) === 1062) {
                return $this->resultadoOperacion(
                    $datos,
                    errores: ['periodo' => 'Ya existe una factura para ese período'],
                );
            }

            throw $exception;
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

    /**
     * Devuelve el estado de negocio sin persistir "vencida" en la base.
     *
     * @param array<string, mixed> $factura
     */
    public function estadoCalculado(array $factura, ?string $hoy = null): string
    {
        if ($factura['estado'] === 'pagada') {
            return 'pagada';
        }

        $fechaActual = $hoy ?? date('Y-m-d');
        $vencimiento = $factura['fecha_segundo_vencimiento']
            ?: $factura['fecha_primer_vencimiento'];

        return $vencimiento < $fechaActual ? 'vencida' : 'pendiente';
    }

    /**
     * @param array<string, mixed> $entrada
     * @return array<string, string>
     */
    private function normalizarDatos(array $entrada): array
    {
        $datos = [];

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
            $valor = $entrada[$campo] ?? '';
            $datos[$campo] = is_string($valor) || is_numeric($valor) ? trim((string) $valor) : '';
        }

        $datos['monto_total'] = str_replace(',', '.', $datos['monto_total']);

        return $datos;
    }

    /**
     * @param array<string, string> $datos
     * @param array<string, mixed> $servicio
     * @return array<string, string>
     */
    private function validarDatos(array &$datos, array $servicio): array
    {
        $errores = [];

        if ($datos['periodo'] === '') {
            $errores['periodo'] = 'El período es obligatorio';
        } elseif (! in_array($datos['periodo'], ['mes', 'anio'], true)) {
            $errores['periodo'] = 'Período inválido';
        }

        if ($datos['periodo_anio'] === '' || ! ctype_digit($datos['periodo_anio'])
            || (int) $datos['periodo_anio'] < 1901 || (int) $datos['periodo_anio'] > 2155
        ) {
            $errores['periodo_anio'] = 'Período inválido';
        }

        if ($datos['periodo'] === 'mes') {
            if (! in_array($datos['periodo_mes'], [
                '01', '02', '03', '04', '05', '06',
                '07', '08', '09', '10', '11', '12',
            ], true)) {
                $errores['periodo_mes'] = 'Período inválido';
            }
        } elseif ($datos['periodo'] === 'anio') {
            $datos['periodo_mes'] = '00';
        }

        if ($datos['fecha_emision'] === '') {
            $errores['fecha_emision'] = 'La fecha de emisión es obligatoria';
        } elseif (! $this->esFechaValida($datos['fecha_emision'])) {
            $errores['fecha_emision'] = 'La fecha de emisión es inválida';
        } elseif ($datos['fecha_emision'] > date('Y-m-d')) {
            $errores['fecha_emision'] = 'La fecha de emisión no puede ser futura';
        }

        if ($datos['fecha_primer_vencimiento'] === '') {
            $errores['fecha_primer_vencimiento'] = 'La fecha de vencimiento es obligatoria';
        } elseif (! $this->esFechaValida($datos['fecha_primer_vencimiento'])) {
            $errores['fecha_primer_vencimiento'] = 'La fecha de vencimiento es inválida';
        }

        if ($datos['fecha_segundo_vencimiento'] !== '') {
            if (! $this->esFechaValida($datos['fecha_segundo_vencimiento'])) {
                $errores['fecha_segundo_vencimiento'] = 'La fecha del segundo vencimiento es inválida';
            } elseif (
                ! isset($errores['fecha_primer_vencimiento'])
                && $datos['fecha_segundo_vencimiento'] <= $datos['fecha_primer_vencimiento']
            ) {
                $errores['fecha_segundo_vencimiento'] =
                    'El segundo vencimiento debe ser posterior al primero';
            }
        }

        if ($datos['monto_total'] === '') {
            $errores['monto_total'] = 'El monto es obligatorio';
        } elseif (is_numeric($datos['monto_total']) && (float) $datos['monto_total'] <= 0) {
            $errores['monto_total'] = 'El monto debe ser mayor a 0';
        } elseif (! preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $datos['monto_total'])) {
            $errores['monto_total'] = 'El monto debe ser válido';
        }

        if ($servicio['numero_medidor'] === null || trim((string) $servicio['numero_medidor']) === '') {
            if ($datos['consumo_del_periodo'] === '') {
                $errores['consumo_del_periodo'] = 'El consumo es obligatorio';
            } elseif (str_starts_with($datos['consumo_del_periodo'], '-')) {
                $errores['consumo_del_periodo'] = 'El consumo no puede ser negativo';
            } elseif (! ctype_digit($datos['consumo_del_periodo'])) {
                $errores['consumo_del_periodo'] = 'El consumo debe ser un número entero';
            } elseif ((float) $datos['consumo_del_periodo'] > 2147483647) {
                $errores['consumo_del_periodo'] = 'El consumo supera el máximo permitido';
            }

            $datos['lectura_anterior'] = '';
            $datos['lectura_actual'] = '';
        } else {
            foreach (['lectura_anterior', 'lectura_actual'] as $campo) {
                if ($datos[$campo] === '') {
                    $errores[$campo] = $campo === 'lectura_anterior'
                        ? 'La lectura anterior es obligatoria'
                        : 'La lectura actual es obligatoria';
                } elseif (str_starts_with($datos[$campo], '-')) {
                    $errores[$campo] = 'La lectura debe ser un número entero no negativo';
                } elseif (! ctype_digit($datos[$campo])) {
                    $errores[$campo] = 'La lectura debe ser un número entero no negativo';
                } elseif ((float) $datos[$campo] > 2147483647) {
                    $errores[$campo] = 'La lectura supera el máximo permitido';
                }
            }

            if (
                ! isset($errores['lectura_anterior'])
                && ! isset($errores['lectura_actual'])
                && (int) $datos['lectura_actual'] < (int) $datos['lectura_anterior']
            ) {
                $errores['lectura_actual'] =
                    'La lectura actual debe ser mayor o igual a la anterior';
            }

            if (
                ! isset($errores['lectura_anterior'])
                && ! isset($errores['lectura_actual'])
            ) {
                $datos['consumo_del_periodo'] = (string) (
                    (int) $datos['lectura_actual'] - (int) $datos['lectura_anterior']
                );
            }
        }

        return $errores;
    }

    private function esFechaValida(string $fecha): bool
    {
        $fechaObjeto = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        $errores = DateTimeImmutable::getLastErrors();

        return $fechaObjeto !== false
            && ($errores === false || ($errores['warning_count'] === 0 && $errores['error_count'] === 0))
            && $fechaObjeto->format('Y-m-d') === $fecha;
    }

    /**
     * @param list<array<string, mixed>> $opciones
     */
    private function normalizarIdFiltro(mixed $valor, array $opciones, string $campoId): string
    {
        if (! is_string($valor) && ! is_int($valor)) {
            return '';
        }

        $valor = trim((string) $valor);

        if ($valor === '' || ! ctype_digit($valor) || (int) $valor < 1) {
            return '';
        }

        foreach ($opciones as $opcion) {
            if ((int) ($opcion[$campoId] ?? 0) === (int) $valor) {
                return $valor;
            }
        }

        return '';
    }

    /**
     * @param list<string> $permitidos
     */
    private function normalizarFiltroTexto(mixed $valor, array $permitidos): string
    {
        if (! is_string($valor)) {
            return '';
        }

        $valor = trim($valor);

        return in_array($valor, $permitidos, true) ? $valor : '';
    }

    private function normalizarFechaFiltro(mixed $valor): string
    {
        if (! is_string($valor)) {
            return '';
        }

        $valor = trim($valor);

        return $this->esFechaValida($valor) ? $valor : '';
    }

    private function normalizarPagina(mixed $valor): int
    {
        if ((! is_string($valor) && ! is_int($valor))
            || ! ctype_digit((string) $valor)
            || (int) $valor < 1
        ) {
            return 1;
        }

        return (int) $valor;
    }

    /**
     * @param array<string, mixed> $servicio
     */
    private function puedeAcceder(array $servicio, int $idUsuario, string $rol): bool
    {
        return $idUsuario > 0 && (
            $rol === 'admin'
            || ($rol === 'propietario' && (int) $servicio['id_propietario'] === $idUsuario)
        );
    }

    /**
     * @param array<string, mixed> $servicio
     */
    private function mensajeServicioNoElegible(array $servicio): ?string
    {
        return match (true) {
            $servicio['estado_propiedad'] === 'inactivo' =>
                'No se pueden cargar facturas en un inmueble inactivo',
            $servicio['estado'] === 'activo' => null,
            $servicio['estado'] === 'pausado' =>
                'No se pueden cargar facturas sobre un servicio pausado',
            default => 'Solo se pueden cargar facturas sobre un servicio activo',
        };
    }

    /**
     * @param array<string, mixed>|null $servicio
     * @return array<string, mixed>
     */
    private function resultadoContexto(
        ?array $servicio = null,
        bool $sinPermiso = false,
        bool $noEncontrado = false,
        ?string $mensaje = null,
    ): array {
        return [
            'servicio' => $servicio,
            'sin_permiso' => $sinPermiso,
            'no_encontrado' => $noEncontrado,
            'mensaje' => $mensaje,
        ];
    }

    /**
     * @param array<string, string> $datos
     * @param array<string, string> $errores
     * @return array<string, mixed>
     */
    private function resultadoOperacion(
        array $datos,
        array $errores = [],
        bool $exito = false,
        ?int $idFactura = null,
        ?string $estadoCalculado = null,
        ?string $mensaje = null,
        bool $sinPermiso = false,
        bool $noEncontrado = false,
    ): array {
        return [
            'exito' => $exito,
            'errores' => $errores,
            'datos' => $datos,
            'id_factura' => $idFactura,
            'estado_calculado' => $estadoCalculado,
            'mensaje' => $mensaje,
            'sin_permiso' => $sinPermiso,
            'no_encontrado' => $noEncontrado,
        ];
    }
}
