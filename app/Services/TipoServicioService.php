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
     * @return array{exito: bool, errores: array<string, string>}
     */
    public function crear(string $tipo, string $categoria, string $unidadMedida, string $proveedor): array
    {
        $tipo = mb_strtolower(trim($tipo), 'UTF-8');
        $categoria = mb_strtolower(trim($categoria), 'UTF-8');
        $unidadMedida = trim($unidadMedida);
        $proveedor = trim($proveedor);
        $errores = [];

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

        if ($errores !== []) {
            return ['exito' => false, 'errores' => $errores];
        }

        if ($this->tipos->existePorTipoYProveedor($tipo, $proveedor)) {
            return $this->duplicado();
        }

        try {
            $creado = $this->tipos->crear([
                'tipo' => $tipo,
                'categoria' => $categoria,
                'unidad_medida' => $unidadMedida,
                'proveedor_predeterminado' => $proveedor,
                'activo' => 1,
            ]);
        } catch (DatabaseException $exception) {
            if (str_contains($exception->getMessage(), 'Unidad de medida no compatible con el tipo de servicio')) {
                return [
                    'exito' => false,
                    'errores' => ['unidad_medida' => 'Unidad de medida inválida'],
                ];
            }

            if ($this->tipos->existePorTipoYProveedor($tipo, $proveedor)) {
                return $this->duplicado();
            }

            throw $exception;
        }

        if (! $creado) {
            if ($this->tipos->existePorTipoYProveedor($tipo, $proveedor)) {
                return $this->duplicado();
            }

            throw new RuntimeException('No se pudo crear el tipo de servicio. Intente nuevamente.');
        }

        return ['exito' => true, 'errores' => []];
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
}
