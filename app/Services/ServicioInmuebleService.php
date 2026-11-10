<?php

namespace App\Services;

use App\Repositories\PropiedadRepository;
use App\Repositories\PropiedadServicioRepository;
use App\Repositories\TipoServicioRepository;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

class ServicioInmuebleService
{
    private const ESTADOS_VIGENTES = ['activo', 'pausado', 'en revision'];

    private const TRANSICIONES = [
        'activo' => ['pausado', 'inactivo'],
        'pausado' => ['en revision', 'inactivo'],
        'en revision' => ['activo', 'inactivo'],
    ];

    private BaseConnection $db;

    public function __construct(
        private ?PropiedadServicioRepository $servicios = null,
        private ?PropiedadRepository $propiedades = null,
        private ?TipoServicioRepository $tipos = null,
        ?BaseConnection $db = null,
    ) {
        $this->servicios ??= new PropiedadServicioRepository();
        $this->propiedades ??= new PropiedadRepository();
        $this->tipos ??= new TipoServicioRepository();
        $this->db = $db ?? db_connect();
    }

    /**
     * @return array{
     *     inmueble: array<string, mixed>|null,
     *     tipos: list<array<string, mixed>>,
     *     sin_tipos: bool,
     *     inmueble_inactivo: bool,
     *     sin_permiso: bool,
     *     no_encontrado: bool
     * }
     */
    public function opcionesAlta(int $idPropiedad, int $idUsuario, string $rol): array
    {
        $inmueble = $this->propiedades->buscarPorIdConDireccion($idPropiedad);

        if ($inmueble === null) {
            return $this->resultadoInmuebleNoEncontrado();
        }

        if (! $this->puedeGestionar($inmueble, $idUsuario, $rol)) {
            return $this->resultadoSinPermiso();
        }

        if ($inmueble['estado'] === 'inactivo') {
            return [
                'inmueble' => $inmueble,
                'tipos' => [],
                'sin_tipos' => false,
                'inmueble_inactivo' => true,
                'sin_permiso' => false,
                'no_encontrado' => false,
            ];
        }

        $tipos = $this->tipos->listarActivosParaVinculacion();

        return [
            'inmueble' => $inmueble,
            'tipos' => $tipos,
            'sin_tipos' => $tipos === [],
            'inmueble_inactivo' => false,
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
     *     id_propiedad_servicio: int|null,
     *     inmueble_inactivo: bool,
     *     sin_permiso: bool,
     *     no_encontrado: bool
     * }
     */
    public function crear(array $entrada, int $idPropiedad, int $idUsuario, string $rol): array
    {
        $datos = $this->normalizarDatos($entrada);
        $inmueble = $this->propiedades->buscarPorIdConDireccion($idPropiedad);

        if ($inmueble === null) {
            return $this->resultadoOperacion($datos, noEncontrado: true);
        }

        if (! $this->puedeGestionar($inmueble, $idUsuario, $rol)) {
            return $this->resultadoOperacion($datos, sinPermiso: true);
        }

        if ($inmueble['estado'] === 'inactivo') {
            return $this->resultadoOperacion(
                $datos,
                errores: ['general' => 'No se pueden agregar servicios a un inmueble inactivo'],
                inmuebleInactivo: true,
            );
        }

        $errores = $this->validarDatos($datos, requiereTipo: true);

        if ($errores !== []) {
            return $this->resultadoOperacion($datos, errores: $errores);
        }

        if (! $this->db->transBegin()) {
            throw new RuntimeException('No se pudo iniciar el alta del servicio.');
        }

        try {
            $inmuebleBloqueado = $this->propiedades->bloquearParaServicios($idPropiedad);

            if ($inmuebleBloqueado === null) {
                $this->db->transRollback();

                return $this->resultadoOperacion($datos, noEncontrado: true);
            }

            if (! $this->puedeGestionar($inmuebleBloqueado, $idUsuario, $rol)) {
                $this->db->transRollback();

                return $this->resultadoOperacion($datos, sinPermiso: true);
            }

            if ($inmuebleBloqueado['estado'] === 'inactivo') {
                $this->db->transRollback();

                return $this->resultadoOperacion(
                    $datos,
                    errores: ['general' => 'No se pueden agregar servicios a un inmueble inactivo'],
                    inmuebleInactivo: true,
                );
            }

            $idTipoServicio = (int) $datos['id_tipo_servicio'];

            if ($this->tipos->buscarActivoPorId($idTipoServicio) === null) {
                $this->db->transRollback();

                return $this->resultadoOperacion(
                    $datos,
                    errores: ['id_tipo_servicio' => 'Tipo de servicio inválido'],
                );
            }

            if ($this->servicios->existeVigente($idPropiedad, $idTipoServicio)) {
                $this->db->transRollback();

                return $this->resultadoOperacion(
                    $datos,
                    errores: ['id_tipo_servicio' => 'El inmueble ya tiene este servicio vinculado'],
                );
            }

            $idServicio = $this->servicios->crear([
                'id_propiedad' => $idPropiedad,
                'id_tipo_servicio' => $idTipoServicio,
                'codigo_pago_electronico' => $datos['codigo_pago_electronico'],
                'numero_cuenta_contrato' => $datos['numero_cuenta_contrato'],
                'numero_medidor' => $datos['numero_medidor'] === '' ? null : $datos['numero_medidor'],
                'domicilio_facturacion' => $datos['domicilio_facturacion'],
                'titular' => $datos['titular'],
                'estado' => 'activo',
            ]);

            if ($idServicio < 1 || ! $this->db->transStatus()) {
                throw new RuntimeException('No se pudo guardar el servicio del inmueble.');
            }

            if (! $this->db->transCommit()) {
                throw new RuntimeException('No se pudo completar el alta del servicio.');
            }
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }

        return $this->resultadoOperacion($datos, idServicio: $idServicio, exito: true);
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
        $servicio = $this->servicios->buscarConDetalle($idServicio);

        if ($servicio === null) {
            return ['servicio' => null, 'sin_permiso' => false, 'no_encontrado' => true];
        }

        if (! $this->puedeGestionar($servicio, $idUsuario, $rol)) {
            return ['servicio' => null, 'sin_permiso' => true, 'no_encontrado' => false];
        }

        return ['servicio' => $servicio, 'sin_permiso' => false, 'no_encontrado' => false];
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
        $servicio = $this->servicios->buscarConDetalle($idServicio);

        if ($servicio === null) {
            return $this->resultadoActualizacion($datos, noEncontrado: true);
        }

        if (! $this->puedeGestionar($servicio, $idUsuario, $rol)) {
            return $this->resultadoActualizacion($datos, sinPermiso: true);
        }

        $errores = $this->validarDatos($datos, requiereTipo: false);

        if ($errores !== []) {
            return $this->resultadoActualizacion($datos, errores: $errores);
        }

        if (! $this->servicios->actualizar($idServicio, [
            'codigo_pago_electronico' => $datos['codigo_pago_electronico'],
            'numero_cuenta_contrato' => $datos['numero_cuenta_contrato'],
            'numero_medidor' => $datos['numero_medidor'] === '' ? null : $datos['numero_medidor'],
            'domicilio_facturacion' => $datos['domicilio_facturacion'],
            'titular' => $datos['titular'],
        ])) {
            throw new RuntimeException('No se pudieron actualizar los datos del servicio.');
        }

        return $this->resultadoActualizacion($datos, exito: true);
    }

    /**
     * @return array{
     *     exito: bool,
     *     errores: array<string, string>,
     *     sin_permiso: bool,
     *     no_encontrado: bool
     * }
     */
    public function cambiarEstado(int $idServicio, string $nuevoEstado, int $idUsuario, string $rol): array
    {
        $servicio = $this->servicios->buscarConDetalle($idServicio);

        if ($servicio === null) {
            return $this->resultadoEstado(noEncontrado: true);
        }

        if (! $this->puedeGestionar($servicio, $idUsuario, $rol)) {
            return $this->resultadoEstado(sinPermiso: true);
        }

        if (! in_array($nuevoEstado, self::TRANSICIONES[$servicio['estado']] ?? [], true)) {
            return $this->resultadoEstado(errores: ['estado' => 'Transición de estado inválida']);
        }

        if (! $this->servicios->actualizar($idServicio, ['estado' => $nuevoEstado])) {
            throw new RuntimeException('No se pudo actualizar el estado del servicio.');
        }

        return $this->resultadoEstado(exito: true);
    }

    /**
     * @return array{
     *     permitido: bool,
     *     mensaje: string|null,
     *     sin_permiso: bool,
     *     no_encontrado: bool
     * }
     */
    public function validarCargaFactura(int $idServicio, int $idUsuario, string $rol): array
    {
        $servicio = $this->servicios->buscarParaFactura($idServicio);

        if ($servicio === null) {
            return [
                'permitido' => false,
                'mensaje' => null,
                'sin_permiso' => false,
                'no_encontrado' => true,
            ];
        }

        if (! $this->puedeGestionar($servicio, $idUsuario, $rol)) {
            return [
                'permitido' => false,
                'mensaje' => null,
                'sin_permiso' => true,
                'no_encontrado' => false,
            ];
        }

        $mensaje = match (true) {
            $servicio['estado_propiedad'] === 'inactivo' => 'No se pueden cargar facturas en un inmueble inactivo',
            $servicio['estado'] === 'activo' => null,
            $servicio['estado'] === 'pausado' => 'No se pueden cargar facturas sobre un servicio pausado',
            default => 'Solo se pueden cargar facturas sobre un servicio activo',
        };

        return [
            'permitido' => $mensaje === null,
            'mensaje' => $mensaje,
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
    private function validarDatos(array $datos, bool $requiereTipo): array
    {
        $errores = [];
        $obligatorios = [
            'codigo_pago_electronico' => 'El CPE/Pagar es obligatorio',
            'numero_cuenta_contrato' => 'El número de cuenta es obligatorio',
            'domicilio_facturacion' => 'El domicilio de facturación es obligatorio',
            'titular' => 'El titular es obligatorio',
        ];

        if ($requiereTipo && $datos['id_tipo_servicio'] === '') {
            $errores['id_tipo_servicio'] = 'El tipo de servicio es obligatorio';
        } elseif ($requiereTipo) {
            $idTipo = filter_var($datos['id_tipo_servicio'], FILTER_VALIDATE_INT);

            if ($idTipo === false || $idTipo < 1) {
                $errores['id_tipo_servicio'] = 'Tipo de servicio inválido';
            }
        }

        foreach ($obligatorios as $campo => $mensaje) {
            if ($datos[$campo] === '') {
                $errores[$campo] = $mensaje;
            }
        }

        foreach ([
            'codigo_pago_electronico' => 20,
            'numero_cuenta_contrato' => 20,
            'numero_medidor' => 20,
            'domicilio_facturacion' => 45,
            'titular' => 50,
        ] as $campo => $maximo) {
            if (mb_strlen($datos[$campo], 'UTF-8') > $maximo) {
                $errores[$campo] = 'El valor ingresado supera la longitud permitida.';
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

        return $rol === 'admin'
            || ($rol === 'propietario' && (int) $recurso['id_propietario'] === $idUsuario);
    }

    /**
     * @param array<string, string> $datos
     * @param array<string, string> $errores
     * @return array<string, mixed>
     */
    private function resultadoOperacion(
        array $datos,
        array $errores = [],
        ?int $idServicio = null,
        bool $exito = false,
        bool $inmuebleInactivo = false,
        bool $sinPermiso = false,
        bool $noEncontrado = false,
    ): array {
        return [
            'exito' => $exito,
            'errores' => $errores,
            'datos' => $datos,
            'id_propiedad_servicio' => $idServicio,
            'inmueble_inactivo' => $inmuebleInactivo,
            'sin_permiso' => $sinPermiso,
            'no_encontrado' => $noEncontrado,
        ];
    }

    /**
     * @param array<string, string> $datos
     * @param array<string, string> $errores
     * @return array<string, mixed>
     */
    private function resultadoActualizacion(
        array $datos,
        array $errores = [],
        bool $exito = false,
        bool $sinPermiso = false,
        bool $noEncontrado = false,
    ): array {
        return [
            'exito' => $exito,
            'errores' => $errores,
            'datos' => $datos,
            'sin_permiso' => $sinPermiso,
            'no_encontrado' => $noEncontrado,
        ];
    }

    /**
     * @param array<string, string> $errores
     * @return array<string, mixed>
     */
    private function resultadoEstado(
        array $errores = [],
        bool $exito = false,
        bool $sinPermiso = false,
        bool $noEncontrado = false,
    ): array {
        return [
            'exito' => $exito,
            'errores' => $errores,
            'sin_permiso' => $sinPermiso,
            'no_encontrado' => $noEncontrado,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resultadoSinPermiso(): array
    {
        return [
            'inmueble' => null,
            'tipos' => [],
            'sin_tipos' => false,
            'inmueble_inactivo' => false,
            'sin_permiso' => true,
            'no_encontrado' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resultadoInmuebleNoEncontrado(): array
    {
        return [
            'inmueble' => null,
            'tipos' => [],
            'sin_tipos' => false,
            'inmueble_inactivo' => false,
            'sin_permiso' => false,
            'no_encontrado' => true,
        ];
    }
}
