<?php

namespace App\Services;

use App\Repositories\PropiedadRepository;
use App\Repositories\PropiedadServicioRepository;
use App\Repositories\TipoServicioRepository;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

class ServicioInmuebleService
{
    private const ESTADOS_VIGENTES = ['activo', 'pausado', 'en revision'];

    private const TRANSICIONES = [
        'activo' => 'pausado',
        'pausado' => 'en revision',
        'en revision' => 'activo',
    ];

    private BaseConnection $db;

    public function __construct(
        private ?PropiedadServicioRepository $servicios = null,
        private ?PropiedadRepository $propiedades = null,
        private ?TipoServicioRepository $tipos = null,
        ?BaseConnection $db = null,
    ) {
        $this->db = $db ?? db_connect();
        $this->servicios ??= new PropiedadServicioRepository();
        $this->propiedades ??= new PropiedadRepository();
        $this->tipos ??= new TipoServicioRepository();
    }

    /**
     * @return array{
     *     inmueble: array<string, mixed>|null,
     *     tipos: list<array<string, mixed>>,
     *     sin_permiso: bool,
     *     no_encontrado: bool,
     *     inmueble_inactivo: bool
     * }
     */
    public function datosAlta(int $idPropiedad, int $idUsuario, string $rol): array
    {
        $inmueble = $this->propiedades->buscarPorIdConDireccion($idPropiedad);

        if ($inmueble === null) {
            return $this->resultadoAlta(null, [], false, true, false);
        }

        if (! $this->puedeGestionar($inmueble, $idUsuario, $rol)) {
            return $this->resultadoAlta(null, [], true, false, false);
        }

        return $this->resultadoAlta(
            $inmueble,
            $this->tipos->listarActivosParaVincular(),
            false,
            false,
            $inmueble['estado'] === 'inactivo',
        );
    }

    /**
     * @param array<string, mixed> $entrada
     * @return array{
     *     exito: bool,
     *     errores: array<string, string>,
     *     datos: array<string, string>,
     *     sin_permiso: bool,
     *     no_encontrado: bool,
     *     inmueble_inactivo: bool
     * }
     */
    public function crear(array $entrada, int $idPropiedad, int $idUsuario, string $rol): array
    {
        $datos = $this->normalizarDatos($entrada);
        $inmueble = $this->propiedades->buscarPorIdConDireccion($idPropiedad);

        if ($inmueble === null) {
            return $this->resultadoOperacion($datos, false, [], false, true, false);
        }

        if (! $this->puedeGestionar($inmueble, $idUsuario, $rol)) {
            return $this->resultadoOperacion($datos, false, [], true, false, false);
        }

        if ($inmueble['estado'] === 'inactivo') {
            return $this->resultadoOperacion(
                $datos,
                false,
                ['general' => 'No se pueden agregar servicios a un inmueble inactivo'],
                false,
                false,
                true,
            );
        }

        if ($this->tipos->listarActivosParaVincular() === []) {
            return $this->resultadoOperacion(
                $datos,
                false,
                ['general' => 'No hay tipos de servicio disponibles. Contacte al administrador.'],
                false,
                false,
                false,
            );
        }

        $errores = $this->validarDatos($datos);
        $idTipo = filter_var($datos['id_tipo_servicio'], FILTER_VALIDATE_INT);
        $tipo = $idTipo !== false && $idTipo !== null && $idTipo > 0
            ? $this->tipos->buscarPorId((int) $idTipo)
            : null;

        if ($datos['id_tipo_servicio'] !== '' && ($tipo === null || (int) $tipo['activo'] !== 1)) {
            $errores['id_tipo_servicio'] = 'El tipo de servicio seleccionado no está disponible';
        }

        if ($errores !== []) {
            return $this->resultadoOperacion($datos, false, $errores, false, false, false);
        }

        if (! $this->db->transBegin()) {
            throw new RuntimeException('No se pudo iniciar la vinculación del servicio.');
        }

        try {
            if (! $this->propiedades->bloquearParaVinculacion($idPropiedad)) {
                throw new RuntimeException('No se pudo bloquear el inmueble para vincular el servicio.');
            }

            $tipoBloqueado = $this->tipos->buscarParaVincularConBloqueo((int) $idTipo);

            if ($tipoBloqueado === null || (int) $tipoBloqueado['activo'] !== 1) {
                $this->db->transRollback();

                return $this->resultadoOperacion(
                    $datos,
                    false,
                    ['id_tipo_servicio' => 'El tipo de servicio seleccionado no está disponible'],
                    false,
                    false,
                    false,
                );
            }

            if ($this->servicios->existeVigente($idPropiedad, (int) $idTipo)) {
                $this->db->transRollback();

                return $this->resultadoOperacion(
                    $datos,
                    false,
                    ['id_tipo_servicio' => 'El inmueble ya tiene este servicio vinculado'],
                    false,
                    false,
                    false,
                );
            }

            $idServicio = $this->servicios->crear([
                'id_propiedad' => $idPropiedad,
                'id_tipo_servicio' => (int) $idTipo,
                'codigo_pago_electronico' => $datos['codigo_pago_electronico'],
                'numero_cuenta_contrato' => $datos['numero_cuenta_contrato'],
                'numero_medidor' => $datos['numero_medidor'] === '' ? null : $datos['numero_medidor'],
                'domicilio_facturacion' => $datos['domicilio_facturacion'],
                'titular' => $datos['titular'],
                'estado' => 'activo',
            ]);

            if ($idServicio < 1 || ! $this->db->transStatus()) {
                throw new RuntimeException('No se pudo guardar el servicio vinculado.');
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('No se pudo completar la vinculación del servicio.');
            }
        } catch (\Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }

        return $this->resultadoOperacion($datos, true, [], false, false, false);
    }

    /**
     * @return array{
     *     servicio: array<string, mixed>|null,
     *     sin_permiso: bool,
     *     no_encontrado: bool
     * }
     */
    public function detalle(int $idServicio, int $idUsuario, string $rol): array
    {
        $servicio = $this->servicios->buscarPorIdConDetalle($idServicio);

        if ($servicio === null) {
            return ['servicio' => null, 'sin_permiso' => false, 'no_encontrado' => true];
        }

        if (! $this->puedeGestionar($servicio, $idUsuario, $rol)) {
            return ['servicio' => null, 'sin_permiso' => true, 'no_encontrado' => false];
        }

        return ['servicio' => $servicio, 'sin_permiso' => false, 'no_encontrado' => false];
    }

    /**
     * @return array{
     *     servicio: array<string, mixed>|null,
     *     tipos: list<array<string, mixed>>,
     *     sin_permiso: bool,
     *     no_encontrado: bool
     * }
     */
    public function datosEdicion(int $idServicio, int $idUsuario, string $rol): array
    {
        $resultado = $this->detalle($idServicio, $idUsuario, $rol);

        if ($resultado['servicio'] === null) {
            return $resultado + ['tipos' => []];
        }

        $tipos = $this->tipos->listarActivosParaVincular();
        $idTipoActual = (int) $resultado['servicio']['id_tipo_servicio'];
        $tipoActualDisponible = false;

        foreach ($tipos as $tipo) {
            if ((int) $tipo['id_tipo_servicio'] === $idTipoActual) {
                $tipoActualDisponible = true;
                break;
            }
        }

        if (! $tipoActualDisponible) {
            $tipoActual = $this->tipos->buscarPorId($idTipoActual);

            if ($tipoActual !== null) {
                $tipos[] = $tipoActual;
            }
        }

        return ['servicio' => $resultado['servicio'], 'tipos' => $tipos] + [
            'sin_permiso' => false,
            'no_encontrado' => false,
        ];
    }

    /**
     * @param array<string, mixed> $entrada
     * @return array{
     *     exito: bool,
     *     errores: array<string, string>,
     *     datos: array<string, string>,
     *     sin_permiso: bool,
     *     no_encontrado: bool
     * }
     */
    public function actualizar(int $idServicio, array $entrada, int $idUsuario, string $rol): array
    {
        $datos = $this->normalizarDatos($entrada);
        $resultado = $this->detalle($idServicio, $idUsuario, $rol);

        if ($resultado['servicio'] === null) {
            return $this->resultadoOperacion(
                $datos,
                false,
                [],
                $resultado['sin_permiso'],
                $resultado['no_encontrado'],
                false,
            );
        }

        $servicio = $resultado['servicio'];
        $errores = $this->validarDatos($datos);
        $idTipo = filter_var($datos['id_tipo_servicio'], FILTER_VALIDATE_INT);
        $tipo = $idTipo !== false && $idTipo !== null && $idTipo > 0
            ? $this->tipos->buscarPorId((int) $idTipo)
            : null;
        $idTipoActual = (int) $servicio['id_tipo_servicio'];
        $esTipoActual = $idTipo !== false && (int) $idTipo === $idTipoActual;

        if (
            $datos['id_tipo_servicio'] !== ''
            && ($tipo === null || ((int) $tipo['activo'] !== 1 && ! $esTipoActual))
        ) {
            $errores['id_tipo_servicio'] = 'El tipo de servicio seleccionado no está disponible';
        }

        if ($errores !== []) {
            return $this->resultadoOperacion($datos, false, $errores, false, false, false);
        }

        if (! $this->db->transBegin()) {
            throw new RuntimeException('No se pudo iniciar la modificación del servicio.');
        }

        try {
            if (! $this->propiedades->bloquearParaVinculacion((int) $servicio['id_propiedad'])) {
                throw new RuntimeException('No se pudo bloquear el inmueble para modificar el servicio.');
            }

            $tipoBloqueado = $this->tipos->buscarParaVincularConBloqueo((int) $idTipo);

            if (
                $tipoBloqueado === null
                || ((int) $tipoBloqueado['activo'] !== 1 && ! $esTipoActual)
            ) {
                $this->db->transRollback();

                return $this->resultadoOperacion(
                    $datos,
                    false,
                    ['id_tipo_servicio' => 'El tipo de servicio seleccionado no está disponible'],
                    false,
                    false,
                    false,
                );
            }

            if ($this->servicios->existeVigente((int) $servicio['id_propiedad'], (int) $idTipo, $idServicio)) {
                $this->db->transRollback();

                return $this->resultadoOperacion(
                    $datos,
                    false,
                    ['id_tipo_servicio' => 'El inmueble ya tiene este servicio vinculado'],
                    false,
                    false,
                    false,
                );
            }

            $actualizado = $this->servicios->actualizar($idServicio, [
                'id_tipo_servicio' => (int) $idTipo,
                'codigo_pago_electronico' => $datos['codigo_pago_electronico'],
                'numero_cuenta_contrato' => $datos['numero_cuenta_contrato'],
                'numero_medidor' => $datos['numero_medidor'] === '' ? null : $datos['numero_medidor'],
                'domicilio_facturacion' => $datos['domicilio_facturacion'],
                'titular' => $datos['titular'],
            ]);

            if (! $actualizado || ! $this->db->transStatus()) {
                throw new RuntimeException('No se pudo modificar el servicio vinculado.');
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('No se pudo completar la modificación del servicio.');
            }
        } catch (\Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }

        return $this->resultadoOperacion($datos, true, [], false, false, false);
    }

    /**
     * @return array{exito: bool, errores: array<string, string>, sin_permiso: bool, no_encontrado: bool}
     */
    public function cambiarEstado(int $idServicio, string $estado, int $idUsuario, string $rol): array
    {
        $resultado = $this->detalle($idServicio, $idUsuario, $rol);

        if ($resultado['servicio'] === null) {
            return [
                'exito' => false,
                'errores' => [],
                'sin_permiso' => $resultado['sin_permiso'],
                'no_encontrado' => $resultado['no_encontrado'],
            ];
        }

        $estadoActual = $resultado['servicio']['estado'];

        if ((self::TRANSICIONES[$estadoActual] ?? null) !== $estado) {
            return [
                'exito' => false,
                'errores' => ['estado' => 'No se permite cambiar el servicio a ese estado.'],
                'sin_permiso' => false,
                'no_encontrado' => false,
            ];
        }

        if (! $this->servicios->actualizar($idServicio, ['estado' => $estado])) {
            throw new RuntimeException('No se pudo modificar el estado del servicio.');
        }

        return ['exito' => true, 'errores' => [], 'sin_permiso' => false, 'no_encontrado' => false];
    }

    /**
     * @return array{exito: bool, errores: array<string, string>, sin_permiso: bool, no_encontrado: bool}
     */
    public function darDeBaja(int $idServicio, int $idUsuario, string $rol): array
    {
        $resultado = $this->detalle($idServicio, $idUsuario, $rol);

        if ($resultado['servicio'] === null) {
            return [
                'exito' => false,
                'errores' => [],
                'sin_permiso' => $resultado['sin_permiso'],
                'no_encontrado' => $resultado['no_encontrado'],
            ];
        }

        if (! in_array($resultado['servicio']['estado'], self::ESTADOS_VIGENTES, true)) {
            return [
                'exito' => false,
                'errores' => ['estado' => 'El servicio ya está inactivo.'],
                'sin_permiso' => false,
                'no_encontrado' => false,
            ];
        }

        if (! $this->servicios->actualizar($idServicio, ['estado' => 'inactivo'])) {
            throw new RuntimeException('No se pudo dar de baja el servicio.');
        }

        return ['exito' => true, 'errores' => [], 'sin_permiso' => false, 'no_encontrado' => false];
    }

    /**
     * @return array{permitido: bool, sin_permiso: bool, no_encontrado: bool}
     */
    public function puedeCargarFacturas(int $idServicio, int $idUsuario, string $rol): array
    {
        $resultado = $this->detalle($idServicio, $idUsuario, $rol);

        if ($resultado['servicio'] === null) {
            return [
                'permitido' => false,
                'sin_permiso' => $resultado['sin_permiso'],
                'no_encontrado' => $resultado['no_encontrado'],
            ];
        }

        return [
            'permitido' => $resultado['servicio']['estado'] === 'activo',
            'sin_permiso' => false,
            'no_encontrado' => false,
        ];
    }

    /**
     * @param array<string, mixed> $entrada
     * @return array<string, string>
     */
    private function normalizarDatos(array $entrada): array
    {
        $datos = [];

        foreach ([
            'id_tipo_servicio',
            'codigo_pago_electronico',
            'numero_cuenta_contrato',
            'numero_medidor',
            'domicilio_facturacion',
            'titular',
        ] as $campo) {
            $valor = $entrada[$campo] ?? '';
            $datos[$campo] = is_string($valor) || is_numeric($valor) ? trim((string) $valor) : '';
        }

        return $datos;
    }

    /**
     * @param array<string, string> $datos
     * @return array<string, string>
     */
    private function validarDatos(array $datos): array
    {
        $errores = [];
        $idTipo = $datos['id_tipo_servicio'];
        $idValidado = filter_var($idTipo, FILTER_VALIDATE_INT);

        if ($idTipo === '') {
            $errores['id_tipo_servicio'] = 'El tipo de servicio es obligatorio';
        } elseif ($idValidado === false || $idValidado < 1) {
            $errores['id_tipo_servicio'] = 'El tipo de servicio es inválido';
        }

        $requeridos = [
            'codigo_pago_electronico' => 'El CPE/Pagar es obligatorio',
            'numero_cuenta_contrato' => 'El número de cuenta es obligatorio',
            'domicilio_facturacion' => 'El domicilio de facturación es obligatorio',
            'titular' => 'El titular es obligatorio',
        ];

        foreach ($requeridos as $campo => $mensaje) {
            if ($datos[$campo] === '') {
                $errores[$campo] = $mensaje;
            }
        }

        $longitudes = [
            'codigo_pago_electronico' => 20,
            'numero_cuenta_contrato' => 20,
            'numero_medidor' => 20,
            'domicilio_facturacion' => 45,
            'titular' => 50,
        ];

        foreach ($longitudes as $campo => $maximo) {
            if (mb_strlen($datos[$campo], 'UTF-8') > $maximo) {
                $errores[$campo] = sprintf('El campo no puede superar los %d caracteres', $maximo);
            }
        }

        return $errores;
    }

    /**
     * @param array<string, mixed> $recurso
     */
    private function puedeGestionar(array $recurso, int $idUsuario, string $rol): bool
    {
        if ($idUsuario < 1) {
            return false;
        }

        if ($rol === 'admin') {
            return true;
        }

        return $rol === 'propietario'
            && (int) ($recurso['id_propietario'] ?? 0) === $idUsuario;
    }

    /**
     * @param array<string, mixed>|null $inmueble
     * @param list<array<string, mixed>> $tipos
     * @return array{
     *     inmueble: array<string, mixed>|null,
     *     tipos: list<array<string, mixed>>,
     *     sin_permiso: bool,
     *     no_encontrado: bool,
     *     inmueble_inactivo: bool
     * }
     */
    private function resultadoAlta(
        ?array $inmueble,
        array $tipos,
        bool $sinPermiso,
        bool $noEncontrado,
        bool $inmuebleInactivo,
    ): array {
        return [
            'inmueble' => $inmueble,
            'tipos' => $tipos,
            'sin_permiso' => $sinPermiso,
            'no_encontrado' => $noEncontrado,
            'inmueble_inactivo' => $inmuebleInactivo,
        ];
    }

    /**
     * @param array<string, string> $datos
     * @param array<string, string> $errores
     * @return array{
     *     exito: bool,
     *     errores: array<string, string>,
     *     datos: array<string, string>,
     *     sin_permiso: bool,
     *     no_encontrado: bool,
     *     inmueble_inactivo: bool
     * }
     */
    private function resultadoOperacion(
        array $datos,
        bool $exito,
        array $errores,
        bool $sinPermiso,
        bool $noEncontrado,
        bool $inmuebleInactivo,
    ): array {
        return [
            'exito' => $exito,
            'errores' => $errores,
            'datos' => $datos,
            'sin_permiso' => $sinPermiso,
            'no_encontrado' => $noEncontrado,
            'inmueble_inactivo' => $inmuebleInactivo,
        ];
    }
}
