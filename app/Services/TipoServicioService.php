<?php

namespace App\Services;

use App\Repositories\TipoServicioRepository;
use CodeIgniter\Database\Exceptions\DatabaseException;
use RuntimeException;

class TipoServicioService
{
    private const TIPOS = [
        'agua',
        'gas',
        'electricidad',
        'internet',
        'telefonia',
        'television',
        'impuesto',
        'expensas',
        'otro',
    ];

    private const CATEGORIAS = ['publico', 'privado'];

    private const UNIDADES = ['kWh', 'm³', 'Gigas', 'Moneda fija'];

    private const UNIDADES_POR_TIPO = [
        'agua' => ['m³', 'Moneda fija'],
        'gas' => ['m³', 'Moneda fija'],
        'electricidad' => ['kWh', 'Moneda fija'],
        'internet' => ['Gigas', 'Moneda fija'],
        'telefonia' => ['Gigas', 'Moneda fija'],
        'television' => ['Gigas', 'Moneda fija'],
        'impuesto' => ['Moneda fija'],
        'expensas' => ['Moneda fija'],
        'otro' => ['kWh', 'm³', 'Gigas', 'Moneda fija'],
    ];

    public function __construct(private ?TipoServicioRepository $tipos = null)
    {
        $this->tipos ??= new TipoServicioRepository();
    }

    /**
     * @return array{
     *     tipos: list<string>,
     *     categorias: list<string>,
     *     unidades: list<string>,
     *     unidades_por_tipo: array<string, list<string>>
     * }
     */
    public function opcionesFormulario(): array
    {
        return [
            'tipos' => self::TIPOS,
            'categorias' => self::CATEGORIAS,
            'unidades' => self::UNIDADES,
            'unidades_por_tipo' => self::UNIDADES_POR_TIPO,
        ];
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array{
     *     tipos: list<array<string, mixed>>,
     *     pager: \CodeIgniter\Pager\PagerInterface|null,
     *     filtros: array{q: string, categoria: string, activo: string}
     * }
     */
    public function listarParaAdministracion(array $filtros = []): array
    {
        $filtrosNormalizados = $this->normalizarFiltros($filtros);
        $pagina = $this->pagina($filtros['page'] ?? null);
        $resultado = $this->tipos->listarParaAdministracion($filtrosNormalizados, $pagina);

        return $resultado + ['filtros' => $filtrosNormalizados];
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array{
     *     tipos: list<array<string, mixed>>,
     *     pager: \CodeIgniter\Pager\PagerInterface|null,
     *     filtros: array{q: string, categoria: string, activo: string}
     * }
     */
    public function listarActivos(array $filtros = []): array
    {
        $filtrosNormalizados = $this->normalizarFiltros($filtros);
        $filtrosNormalizados['activo'] = '';
        $pagina = $this->pagina($filtros['page'] ?? null);
        $resultado = $this->tipos->listarActivos($filtrosNormalizados, $pagina);

        return $resultado + ['filtros' => $filtrosNormalizados];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->tipos->buscarPorId($id);
    }

    /**
     * @return array{exito: bool, errores: array<string, string>}
     */
    public function crear(string $tipo, string $categoria, string $unidadMedida, string $proveedor): array
    {
        $datos = $this->normalizarDatos($tipo, $categoria, $unidadMedida, $proveedor);
        $errores = $this->validarDatos($datos);

        if ($errores !== []) {
            return ['exito' => false, 'errores' => $errores];
        }

        if ($this->tipos->existePorTipoYProveedor($datos['tipo'], $datos['proveedor_predeterminado'])) {
            return $this->duplicado();
        }

        try {
            $creado = $this->tipos->crear([
                ...$datos,
                'activo' => 1,
            ]);
        } catch (DatabaseException $exception) {
            if (str_contains($exception->getMessage(), 'Unidad de medida no compatible con el tipo de servicio')) {
                return [
                    'exito' => false,
                    'errores' => ['unidad_medida' => 'Unidad de medida inválida'],
                ];
            }

            if ($this->tipos->existePorTipoYProveedor($datos['tipo'], $datos['proveedor_predeterminado'])) {
                return $this->duplicado();
            }

            throw $exception;
        }

        if (! $creado) {
            if ($this->tipos->existePorTipoYProveedor($datos['tipo'], $datos['proveedor_predeterminado'])) {
                return $this->duplicado();
            }

            throw new RuntimeException('No se pudo crear el tipo de servicio. Intente nuevamente.');
        }

        return ['exito' => true, 'errores' => []];
    }

    /**
     * @return array{exito: bool, errores: array<string, string>}
     */
    public function actualizar(
        int $id,
        string $tipo,
        string $categoria,
        string $unidadMedida,
        string $proveedor,
        string $activo,
    ): array {
        $datos = $this->normalizarDatos($tipo, $categoria, $unidadMedida, $proveedor);
        $datos['activo'] = $activo;
        $errores = $this->validarDatos($datos);

        if (! in_array($activo, ['0', '1'], true)) {
            $errores['activo'] = 'Estado inválido';
        }

        if ($id < 1 || $this->tipos->buscarPorId($id) === null) {
            $errores['general'] = 'No se encontró el tipo de servicio solicitado.';
        }

        if ($errores !== []) {
            return ['exito' => false, 'errores' => $errores];
        }

        $datos['activo'] = (int) $activo;

        if ($this->tipos->existeOtroPorTipoYProveedor(
            $datos['tipo'],
            $datos['proveedor_predeterminado'],
            $id,
        )) {
            return $this->duplicado();
        }

        try {
            $actualizado = $this->tipos->actualizar($id, $datos);
        } catch (DatabaseException $exception) {
            if (str_contains($exception->getMessage(), 'Unidad de medida no compatible con el tipo de servicio')) {
                return [
                    'exito' => false,
                    'errores' => ['unidad_medida' => 'Unidad de medida inválida'],
                ];
            }

            if ($this->tipos->existeOtroPorTipoYProveedor(
                $datos['tipo'],
                $datos['proveedor_predeterminado'],
                $id,
            )) {
                return $this->duplicado();
            }

            throw $exception;
        }

        if (! $actualizado) {
            if ($this->tipos->existeOtroPorTipoYProveedor(
                $datos['tipo'],
                $datos['proveedor_predeterminado'],
                $id,
            )) {
                return $this->duplicado();
            }

            throw new RuntimeException('No se pudo modificar el tipo de servicio. Intente nuevamente.');
        }

        return ['exito' => true, 'errores' => []];
    }

    /**
     * @return array{exito: bool, errores: array<string, string>}
     */
    public function cambiarEstado(int $id, string $activo): array
    {
        if (! in_array($activo, ['0', '1'], true)) {
            return ['exito' => false, 'errores' => ['activo' => 'Estado inválido']];
        }

        if ($id < 1 || $this->tipos->buscarPorId($id) === null) {
            return [
                'exito' => false,
                'errores' => ['general' => 'No se encontró el tipo de servicio solicitado.'],
            ];
        }

        if (! $this->tipos->actualizar($id, ['activo' => (int) $activo])) {
            throw new RuntimeException('No se pudo modificar el estado del tipo de servicio. Intente nuevamente.');
        }

        return ['exito' => true, 'errores' => []];
    }

    /**
     * @return array{tipo: string, categoria: string, unidad_medida: string, proveedor_predeterminado: string}
     */
    private function normalizarDatos(string $tipo, string $categoria, string $unidadMedida, string $proveedor): array
    {
        return [
            'tipo' => mb_strtolower(trim($tipo), 'UTF-8'),
            'categoria' => mb_strtolower(trim($categoria), 'UTF-8'),
            'unidad_medida' => trim($unidadMedida),
            'proveedor_predeterminado' => trim($proveedor),
        ];
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, string>
     */
    private function validarDatos(array $datos): array
    {
        $errores = [];
        $tipo = $datos['tipo'];
        $categoria = $datos['categoria'];
        $unidadMedida = $datos['unidad_medida'];
        $proveedor = $datos['proveedor_predeterminado'];

        if ($tipo === '') {
            $errores['tipo'] = 'El nombre es obligatorio';
        } elseif (! in_array($tipo, self::TIPOS, true)) {
            $errores['tipo'] = 'Nombre inválido';
        }

        if ($categoria === '') {
            $errores['categoria'] = 'La categoría es obligatoria';
        } elseif (! in_array($categoria, self::CATEGORIAS, true)) {
            $errores['categoria'] = 'Categoría inválida';
        }

        if ($unidadMedida === '') {
            $errores['unidad_medida'] = 'La unidad de medida es obligatoria';
        } elseif (! in_array($unidadMedida, self::UNIDADES, true)) {
            $errores['unidad_medida'] = 'Unidad de medida inválida';
        } elseif (
            in_array($tipo, self::TIPOS, true)
            && ! in_array($unidadMedida, self::UNIDADES_POR_TIPO[$tipo], true)
        ) {
            $errores['unidad_medida'] = 'Unidad de medida inválida';
        }

        if ($proveedor === '') {
            $errores['proveedor_predeterminado'] = 'El proveedor es obligatorio';
        } elseif (mb_strlen($proveedor, 'UTF-8') > 45) {
            $errores['proveedor_predeterminado'] = 'El proveedor no puede superar los 45 caracteres';
        }

        return $errores;
    }

    /**
     * @return array{exito: false, errores: array{tipo: string}}
     */
    private function duplicado(): array
    {
        return [
            'exito' => false,
            'errores' => ['tipo' => 'Ya existe un tipo de servicio con ese nombre'],
        ];
    }

    /**
     * @param array<string, mixed> $filtros
     * @return array{q: string, categoria: string, activo: string}
     */
    private function normalizarFiltros(array $filtros): array
    {
        $q = $filtros['q'] ?? '';
        $q = is_string($q) ? trim($q) : '';

        $categoria = $filtros['categoria'] ?? '';
        $categoria = is_string($categoria) && in_array($categoria, self::CATEGORIAS, true)
            ? $categoria
            : '';

        $activo = $filtros['activo'] ?? '';
        $activo = is_string($activo) && in_array($activo, ['0', '1'], true)
            ? $activo
            : '';

        return [
            'q' => $q,
            'categoria' => $categoria,
            'activo' => $activo,
        ];
    }

    private function pagina(mixed $pagina): int
    {
        $pagina = filter_var($pagina, FILTER_VALIDATE_INT);

        return $pagina !== false && $pagina !== null && $pagina > 0 ? $pagina : 1;
    }
}
