<?php

namespace Tests\Unit\Services;

use App\Repositories\TipoServicioRepository;
use App\Services\TipoServicioService;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class TipoServicioServiceTest extends CIUnitTestCase
{
    public function testCreatesAnActiveServiceTypeWithCanonicalValues(): void
    {
        [$service, $tipos] = $this->crearServicio();

        $resultado = $service->crear(' ELECTRICIDAD ', ' PUBLICO ', 'kWh', ' Edenor ');

        $this->assertTrue($resultado['exito']);
        $this->assertSame([], $resultado['errores']);
        $this->assertSame([
            'tipo' => 'electricidad',
            'categoria' => 'publico',
            'unidad_medida' => 'kWh',
            'proveedor_predeterminado' => 'Edenor',
            'activo' => 1,
        ], $tipos->registro);
    }

    public function testReturnsRequiredMessagesForAllFields(): void
    {
        [$service] = $this->crearServicio();

        $resultado = $service->crear('', '', '', '');

        $this->assertSame('El nombre es obligatorio', $resultado['errores']['tipo']);
        $this->assertSame('La categoría es obligatoria', $resultado['errores']['categoria']);
        $this->assertSame('La unidad de medida es obligatoria', $resultado['errores']['unidad_medida']);
        $this->assertSame('El proveedor es obligatorio', $resultado['errores']['proveedor_predeterminado']);
    }

    public function testRejectsInvalidCategoryAndUnit(): void
    {
        [$service] = $this->crearServicio();

        $categoria = $service->crear('agua', 'mixto', 'm³', 'Aguas');
        $unidad = $service->crear('agua', 'publico', 'Pesos', 'Aguas');

        $this->assertSame('Categoría inválida', $categoria['errores']['categoria']);
        $this->assertSame('Unidad de medida inválida', $unidad['errores']['unidad_medida']);
    }

    public function testRejectsUnitsThatAreNotCompatibleWithTheSelectedType(): void
    {
        [$service] = $this->crearServicio();

        $resultado = $service->crear('electricidad', 'publico', 'm³', 'Edenor');

        $this->assertSame('Unidad de medida inválida', $resultado['errores']['unidad_medida']);
    }

    public function testRejectsCaseInsensitiveDuplicateTypeAndProvider(): void
    {
        [$service, $tipos] = $this->crearServicio(existe: true);

        $resultado = $service->crear('agua', 'publico', 'm³', ' aguas ');

        $this->assertSame(
            'Ya existe un tipo de servicio con ese nombre',
            $resultado['errores']['tipo'],
        );
        $this->assertNull($tipos->registro);
    }

    public function testReportsConcurrentDuplicateButRethrowsOtherDatabaseErrors(): void
    {
        $tipos = new class extends TipoServicioRepository {
            private bool $creado = false;

            public function __construct() {}

            public function existePorTipoYProveedor(string $tipo, string $proveedor): bool
            {
                return $this->creado;
            }

            public function crear(array $datos): bool
            {
                $this->creado = true;

                throw new DatabaseException('Duplicate entry');
            }
        };
        $service = new TipoServicioService($tipos);

        $resultado = $service->crear('agua', 'publico', 'm³', 'Aguas');

        $this->assertSame(
            'Ya existe un tipo de servicio con ese nombre',
            $resultado['errores']['tipo'],
        );

        $repositorioConError = new class extends TipoServicioRepository {
            public function __construct() {}

            public function existePorTipoYProveedor(string $tipo, string $proveedor): bool
            {
                return false;
            }

            public function crear(array $datos): bool
            {
                throw new DatabaseException('Database unavailable');
            }
        };

        $this->expectException(DatabaseException::class);
        (new TipoServicioService($repositorioConError))->crear('agua', 'publico', 'm³', 'Aguas');
    }

    public function testMapsTriggerRejectionToTheAcceptanceMessage(): void
    {
        $tipos = new class extends TipoServicioRepository {
            public function __construct() {}

            public function existePorTipoYProveedor(string $tipo, string $proveedor): bool
            {
                return false;
            }

            public function crear(array $datos): bool
            {
                throw new DatabaseException('Unidad de medida no compatible con el tipo de servicio');
            }
        };

        $resultado = (new TipoServicioService($tipos))->crear('agua', 'publico', 'm³', 'Aguas');

        $this->assertSame('Unidad de medida inválida', $resultado['errores']['unidad_medida']);
    }

    public function testExposesOnlyCanonicalFormOptionsAndCompatibleUnits(): void
    {
        [$service] = $this->crearServicio();
        $opciones = $service->opcionesFormulario();

        $this->assertSame(
            ['agua', 'gas', 'electricidad', 'internet', 'telefonia', 'television', 'impuesto', 'expensas', 'otro'],
            $opciones['tipos'],
        );
        $this->assertSame(['publico', 'privado'], $opciones['categorias']);
        $this->assertSame(['kWh', 'm³', 'Gigas', 'Moneda fija'], $opciones['unidades']);
        $this->assertSame(['Moneda fija'], $opciones['unidades_por_tipo']['impuesto']);
    }

    /**
     * @return array{TipoServicioService, TipoServicioRepository&object}
     */
    private function crearServicio(bool $existe = false): array
    {
        $tipos = new class($existe) extends TipoServicioRepository {
            /** @var array<string, mixed>|null */
            public ?array $registro = null;

            public function __construct(private bool $existe) {}

            public function existePorTipoYProveedor(string $tipo, string $proveedor): bool
            {
                return $this->existe
                    && $tipo === 'agua'
                    && mb_strtolower($proveedor, 'UTF-8') === 'aguas';
            }

            public function crear(array $datos): bool
            {
                $this->registro = $datos;

                return true;
            }
        };

        return [new TipoServicioService($tipos), $tipos];
    }
}
