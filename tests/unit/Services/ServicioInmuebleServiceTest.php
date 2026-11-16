<?php

namespace Tests\Unit\Services;

use App\Repositories\PropiedadServicioRepository;
use App\Services\ServicioInmuebleService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ServicioInmuebleServiceTest extends CIUnitTestCase
{
    public function testOnlyActiveServicesAllowInvoiceEntryForTheirOwnerOrAnAdministrator(): void
    {
        foreach ([
            'activo' => true,
            'pausado' => false,
            'en revision' => false,
            'inactivo' => false,
        ] as $estado => $esperado) {
            $servicios = new class($estado) extends PropiedadServicioRepository {
                public function __construct(private string $estado) {}

                public function buscarPorIdConDetalle(int $id): ?array
                {
                    return [
                        'id_propiedad_servicio' => $id,
                        'id_propietario' => 7,
                        'estado' => $this->estado,
                    ];
                }
            };

            $service = new ServicioInmuebleService(servicios: $servicios);

            $this->assertSame(
                $esperado,
                $service->puedeCargarFacturas(3, 7, 'propietario')['permitido'],
                'Estado: ' . $estado,
            );

            $otroPropietario = $service->puedeCargarFacturas(3, 8, 'propietario');
            $this->assertTrue($otroPropietario['sin_permiso']);
            $this->assertFalse($service->puedeCargarFacturas(3, 8, 'admin')['sin_permiso']);
        }
    }

    public function testOnlyDocumentedStateTransitionsAreAccepted(): void
    {
        foreach ([
            ['activo', 'pausado', true],
            ['pausado', 'en revision', true],
            ['en revision', 'activo', true],
            ['activo', 'en revision', false],
            ['pausado', 'activo', false],
            ['inactivo', 'activo', false],
        ] as [$estadoActual, $estadoNuevo, $esperado]) {
            $servicios = new class($estadoActual) extends PropiedadServicioRepository {
                /** @var array<string, mixed>|null */
                public ?array $actualizacion = null;

                public function __construct(private string $estado) {}

                public function buscarPorIdConDetalle(int $id): ?array
                {
                    return [
                        'id_propiedad_servicio' => $id,
                        'id_propietario' => 7,
                        'estado' => $this->estado,
                    ];
                }

                public function actualizar(int $id, array $datos): bool
                {
                    $this->actualizacion = $datos;

                    return true;
                }
            };

            $service = new ServicioInmuebleService(servicios: $servicios);
            $resultado = $service->cambiarEstado(3, $estadoNuevo, 7, 'propietario');

            $this->assertSame($esperado, $resultado['exito'], $estadoActual . ' -> ' . $estadoNuevo);
            $this->assertSame(
                $esperado ? ['estado' => $estadoNuevo] : null,
                $servicios->actualizacion,
            );
        }
    }
}
