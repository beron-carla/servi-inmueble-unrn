<?php

namespace Tests\Feature;

use App\Database\Seeds\DatabaseSeeder;
use App\Services\FacturaService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use DateTimeImmutable;

/**
 * @internal
 */
final class FacturasTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testOwnerCreatesMonthlyMeteredInvoiceWithSnapshotAndCalculatedStatus(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'factura.mensual@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Depto mensual', 'ocupado');
        $idServicio = $this->crearServicio(
            $idPropiedad,
            $this->idTipoServicio('electricidad'),
            'activo',
            'MED-123',
        );
        $this->iniciarSesion($idPropietario, 'propietario');

        $hoy = new DateTimeImmutable('today');
        $datos = $this->datosFactura([
            'periodo' => 'mes',
            'periodo_mes' => $hoy->format('m'),
            'periodo_anio' => $hoy->format('Y'),
            'fecha_primer_vencimiento' => $hoy->modify('-3 days')->format('Y-m-d'),
            'fecha_segundo_vencimiento' => $hoy->modify('-1 day')->format('Y-m-d'),
            'lectura_anterior' => '100',
            'lectura_actual' => '137',
        ]);
        $respuesta = $this->postFactura($idServicio, $datos);

        $factura = $this->db->table('factura')
            ->where('id_propiedad_servicio', $idServicio)
            ->get()
            ->getRowArray();

        $this->assertNotNull($factura);
        $this->assertSame('mes', $factura['periodo']);
        $this->assertSame($hoy->format('m'), $factura['periodo_mes']);
        $this->assertSame($hoy->format('Y'), (string) $factura['periodo_anio']);
        $this->assertSame(37, (int) $factura['consumo_del_periodo']);
        $this->assertSame(100, (int) $factura['lectura_anterior']);
        $this->assertSame(137, (int) $factura['lectura_actual']);
        $this->assertSame('kWh', $factura['unidad_medida']);
        $this->assertSame('Edenor', $factura['proveedor']);
        $this->assertSame('publico', $factura['categoria_factura']);
        $this->assertSame('pendiente', $factura['estado']);
        $respuesta->assertRedirectTo('/servicios-inmueble/' . $idServicio);
        $this->assertStringContainsString('Estado calculado: Vencida', session()->getFlashdata('success'));
    }

    public function testAnnualInvoiceWithoutMeterUsesManualConsumptionAndPeriod00(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'factura.anual@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Local anual', 'disponible');
        $idServicio = $this->crearServicio(
            $idPropiedad,
            $this->idTipoServicio('expensas'),
            'activo',
        );
        $this->iniciarSesion($idPropietario, 'propietario');

        $respuesta = $this->postFactura($idServicio, $this->datosFactura([
            'periodo' => 'anio',
            'periodo_mes' => '12',
            'periodo_anio' => '2026',
            'consumo_del_periodo' => '240',
        ]));

        $factura = $this->db->table('factura')
            ->where('id_propiedad_servicio', $idServicio)
            ->get()
            ->getRowArray();

        $this->assertNotNull($factura);
        $this->assertSame('anio', $factura['periodo']);
        $this->assertSame('00', $factura['periodo_mes']);
        $this->assertSame(240, (int) $factura['consumo_del_periodo']);
        $this->assertNull($factura['lectura_anterior']);
        $this->assertNull($factura['lectura_actual']);
        $this->assertSame('Moneda fija', $factura['unidad_medida']);
        $respuesta->assertRedirectTo('/servicios-inmueble/' . $idServicio);
    }

    public function testRequiredValuesAndInvalidMonthlyPeriodUseAcceptanceMessages(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'factura.requeridos@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Inmueble requeridos', 'ocupado');
        $idServicio = $this->crearServicio(
            $idPropiedad,
            $this->idTipoServicio('agua'),
            'activo',
        );
        $this->iniciarSesion($idPropietario, 'propietario');

        $this->postFactura($idServicio, $this->datosFactura([
            'periodo' => '',
            'fecha_primer_vencimiento' => '',
            'monto_total' => '',
        ]))->assertRedirectTo('/servicios-inmueble/' . $idServicio . '/facturas/nuevo');

        $errores = session()->getFlashdata('factura_errores');
        $this->assertSame('El período es obligatorio', $errores['periodo']);
        $this->assertSame('La fecha de vencimiento es obligatoria', $errores['fecha_primer_vencimiento']);
        $this->assertSame('El monto es obligatorio', $errores['monto_total']);
        $this->assertSame(0, $this->db->table('factura')
            ->where('id_propiedad_servicio', $idServicio)
            ->countAllResults());

        $this->postFactura($idServicio, $this->datosFactura([
            'periodo_mes' => '13',
        ]))->assertRedirectTo('/servicios-inmueble/' . $idServicio . '/facturas/nuevo');

        $this->assertSame('Período inválido', session()->getFlashdata('factura_errores')['periodo_mes']);
    }

    public function testDuplicatePeriodsAreRejectedButAnAnnualPeriodIsDistinct(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'factura.duplicada@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Inmueble duplicada', 'ocupado');
        $idServicio = $this->crearServicio(
            $idPropiedad,
            $this->idTipoServicio('gas'),
            'activo',
        );
        $this->iniciarSesion($idPropietario, 'propietario');

        $mensual = $this->datosFactura([
            'periodo' => 'mes',
            'periodo_mes' => '01',
            'periodo_anio' => '2026',
        ]);
        $this->postFactura($idServicio, $mensual)->assertRedirectTo('/servicios-inmueble/' . $idServicio);
        $this->postFactura($idServicio, $mensual)
            ->assertRedirectTo('/servicios-inmueble/' . $idServicio . '/facturas/nuevo');

        $this->assertSame(
            'Ya existe una factura para ese período',
            session()->getFlashdata('factura_errores')['periodo'],
        );

        $this->postFactura($idServicio, $this->datosFactura([
            'periodo' => 'anio',
            'periodo_mes' => '01',
            'periodo_anio' => '2026',
        ]))->assertRedirectTo('/servicios-inmueble/' . $idServicio);
        $this->assertSame(2, $this->db->table('factura')
            ->where('id_propiedad_servicio', $idServicio)
            ->countAllResults());
    }

    public function testReadingsDueDatesAndFutureIssueDateAreValidated(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'factura.fechas@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Inmueble fechas', 'ocupado');
        $idServicio = $this->crearServicio(
            $idPropiedad,
            $this->idTipoServicio('electricidad'),
            'activo',
            'MED-456',
        );
        $this->iniciarSesion($idPropietario, 'propietario');
        $manana = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');

        $this->postFactura($idServicio, $this->datosFactura([
            'fecha_emision' => $manana,
            'fecha_primer_vencimiento' => '2026-12-10',
            'fecha_segundo_vencimiento' => '2026-12-09',
            'monto_total' => '0',
            'lectura_anterior' => '20',
            'lectura_actual' => '19',
        ]))->assertRedirectTo('/servicios-inmueble/' . $idServicio . '/facturas/nuevo');

        $errores = session()->getFlashdata('factura_errores');
        $this->assertSame('La fecha de emisión no puede ser futura', $errores['fecha_emision']);
        $this->assertSame(
            'El segundo vencimiento debe ser posterior al primero',
            $errores['fecha_segundo_vencimiento'],
        );
        $this->assertSame('El monto debe ser mayor a 0', $errores['monto_total']);
        $this->assertSame(
            'La lectura actual debe ser mayor o igual a la anterior',
            $errores['lectura_actual'],
        );

        $this->postFactura($idServicio, $this->datosFactura([
            'monto_total' => '-1',
            'lectura_anterior' => '20',
            'lectura_actual' => '20',
        ]))->assertRedirectTo('/servicios-inmueble/' . $idServicio . '/facturas/nuevo');
        $this->assertSame(
            'El monto debe ser mayor a 0',
            session()->getFlashdata('factura_errores')['monto_total'],
        );

        $this->assertSame(0, $this->db->table('factura')
            ->where('id_propiedad_servicio', $idServicio)
            ->countAllResults());
    }

    public function testNegativeManualConsumptionIsRejected(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'factura.consumo@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Inmueble consumo', 'ocupado');
        $idServicio = $this->crearServicio(
            $idPropiedad,
            $this->idTipoServicio('expensas'),
            'activo',
        );
        $this->iniciarSesion($idPropietario, 'propietario');

        $this->postFactura($idServicio, $this->datosFactura([
            'consumo_del_periodo' => '-1',
        ]))->assertRedirectTo('/servicios-inmueble/' . $idServicio . '/facturas/nuevo');

        $this->assertSame(
            'El consumo no puede ser negativo',
            session()->getFlashdata('factura_errores')['consumo_del_periodo'],
        );
    }

    public function testOwnerIsForbiddenForOtherOwnersAndAdminCanCreateInvoices(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'factura.propietario@example.test');
        $idOtroPropietario = $this->crearUsuario('propietario', 'factura.otro@example.test');
        $idPropiedadPropia = $this->crearPropiedad($idPropietario, 'Propiedad propia', 'ocupado');
        $idPropiedadAjena = $this->crearPropiedad($idOtroPropietario, 'Propiedad ajena', 'ocupado');
        $idServicioPropio = $this->crearServicio(
            $idPropiedadPropia,
            $this->idTipoServicio('agua'),
            'activo',
        );
        $idServicioAjeno = $this->crearServicio(
            $idPropiedadAjena,
            $this->idTipoServicio('gas'),
            'activo',
        );
        $this->iniciarSesion($idPropietario, 'propietario');

        $this->get('/servicios-inmueble/' . $idServicioPropio . '/facturas/nuevo')->assertOK();
        $token = $this->tokenFormulario($idServicioPropio);
        $getDenegado = $this->get('/servicios-inmueble/' . $idServicioAjeno . '/facturas/nuevo');
        $getDenegado->assertStatus(403);
        $denegado = $this->post(
            '/servicios-inmueble/' . $idServicioAjeno . '/facturas',
            $this->datosFactura(['csrf_test_name' => $token]),
        );
        $denegado->assertStatus(403);
        $this->assertStringContainsString(
            'No tiene permisos para esta acción',
            $denegado->response()->getBody(),
        );
        $this->assertSame(0, $this->db->table('factura')
            ->where('id_propiedad_servicio', $idServicioAjeno)
            ->countAllResults());

        $idAdmin = $this->crearUsuario('admin', 'factura.admin@example.test');
        $this->iniciarSesion($idAdmin, 'admin');
        $this->postFactura($idServicioAjeno, $this->datosFactura())
            ->assertRedirectTo('/servicios-inmueble/' . $idServicioAjeno);
        $this->assertSame(1, $this->db->table('factura')
            ->where('id_propiedad_servicio', $idServicioAjeno)
            ->countAllResults());
    }

    public function testPausedReviewAndInactivePropertiesRejectInvoiceCreation(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'factura.estados@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Inmueble estados', 'ocupado');
        $idPropiedadInactiva = $this->crearPropiedad(
            $idPropietario,
            'Inmueble inactivo para facturas',
            'inactivo',
        );
        $tipo = $this->idTipoServicio('internet');
        $servicios = [
            'pausado' => $this->crearServicio($idPropiedad, $tipo, 'pausado'),
            'en revision' => $this->crearServicio($idPropiedad, $tipo, 'en revision'),
            'inactivo' => $this->crearServicio($idPropiedad, $tipo, 'inactivo'),
            'propiedad_inactiva' => $this->crearServicio($idPropiedadInactiva, $tipo, 'activo'),
        ];
        $this->iniciarSesion($idPropietario, 'propietario');

        $idServicioActivo = $this->crearServicio(
            $idPropiedad,
            $this->idTipoServicio('agua'),
            'activo',
        );
        $detalleActivo = $this->get('/servicios-inmueble/' . $idServicioActivo);
        $this->assertStringContainsString('Registrar factura', $detalleActivo->response()->getBody());

        foreach ($servicios as $estado => $idServicio) {
            $detalle = $this->get('/servicios-inmueble/' . $idServicio);
            $this->assertStringNotContainsString('Registrar factura', $detalle->response()->getBody());

            $respuesta = $this->post(
                '/servicios-inmueble/' . $idServicio . '/facturas',
                $this->datosFactura(['csrf_test_name' => $this->tokenFormulario($idServicioActivo)]),
            );
            $respuesta->assertRedirectTo('/servicios-inmueble/' . $idServicio);

            $mensajeEsperado = match ($estado) {
                'pausado' => 'No se pueden cargar facturas sobre un servicio pausado',
                'propiedad_inactiva' => 'No se pueden cargar facturas en un inmueble inactivo',
                default => 'Solo se pueden cargar facturas sobre un servicio activo',
            };
            $this->assertSame($mensajeEsperado, session()->getFlashdata('error'));
        }

        $this->assertSame(0, $this->db->table('factura')
            ->whereIn('id_propiedad_servicio', array_values($servicios))
            ->countAllResults());
    }

    public function testCalculatedInvoiceStateUsesTheApplicableDueDate(): void
    {
        $service = new FacturaService();

        $this->assertSame('pendiente', $service->estadoCalculado([
            'estado' => 'pendiente',
            'fecha_primer_vencimiento' => '2026-11-01',
            'fecha_segundo_vencimiento' => '2026-11-20',
        ], '2026-11-09'));
        $this->assertSame('vencida', $service->estadoCalculado([
            'estado' => 'pendiente',
            'fecha_primer_vencimiento' => '2026-11-01',
            'fecha_segundo_vencimiento' => null,
        ], '2026-11-09'));
        $this->assertSame('pagada', $service->estadoCalculado([
            'estado' => 'pagada',
            'fecha_primer_vencimiento' => '2026-11-01',
            'fecha_segundo_vencimiento' => null,
        ], '2026-11-09'));
    }

    public function testOwnerInvoiceListIsScopedSortedAndShowsCalculatedStatusAndTotals(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'factura.listado@example.test');
        $idOtroPropietario = $this->crearUsuario('propietario', 'factura.listado.otro@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Depto listado', 'ocupado');
        $idPropiedadAjena = $this->crearPropiedad($idOtroPropietario, 'Inmueble ajeno listado', 'ocupado');
        $idServicio = $this->crearServicio(
            $idPropiedad,
            $this->idTipoServicio('electricidad'),
            'activo',
        );
        $idServicioAjeno = $this->crearServicio(
            $idPropiedadAjena,
            $this->idTipoServicio('agua'),
            'activo',
        );
        $hoy = new DateTimeImmutable('today');
        $primerVencimientoFuturo = $hoy->modify('+15 days')->format('Y-m-d');
        $vencimientoFuturo = $hoy->modify('+10 days')->format('Y-m-d');
        $vencimientoVencido = $hoy->modify('-5 days')->format('Y-m-d');
        $vencimientoPagado = $hoy->modify('-20 days')->format('Y-m-d');

        $this->crearFactura(
            $idServicio,
            '01',
            'pendiente',
            $primerVencimientoFuturo,
            '12000.00',
            $vencimientoFuturo,
        );
        $this->crearFactura($idServicio, '02', 'pendiente', $vencimientoVencido, '4300.00');
        $this->crearFactura($idServicio, '03', 'pagada', $vencimientoPagado, '13500.00');
        $this->crearFactura($idServicioAjeno, '04', 'pagada', $vencimientoFuturo, '99000.00');
        $this->iniciarSesion($idPropietario, 'propietario');

        $respuesta = $this->get('/facturas');
        $cuerpo = $respuesta->response()->getBody();

        $respuesta->assertOK();
        $this->assertStringContainsString('Vencida', $cuerpo);
        $this->assertStringContainsString('Total Pendiente', $cuerpo);
        $this->assertStringContainsString('$ 16.300,00', $cuerpo);
        $this->assertStringContainsString('$ 13.500,00', $cuerpo);
        $this->assertStringContainsString('$ 29.800,00', $cuerpo);
        $this->assertStringContainsString('Electricidad', $cuerpo);
        $this->assertStringContainsString('Proveedor de prueba', $cuerpo);
        $this->assertStringContainsString(formato_fecha($vencimientoFuturo), $cuerpo);
        $this->assertStringNotContainsString(formato_fecha($primerVencimientoFuturo), $cuerpo);
        $this->assertStringContainsString('Vencimiento aplicable', $cuerpo);
        $this->assertStringContainsString('Período', $cuerpo);
        $this->assertStringContainsString('Monto', $cuerpo);
        $this->assertStringNotContainsString('Inmueble ajeno listado', $cuerpo);
        $this->assertStringContainsString('href="' . route_to('facturas.index') . '"', $cuerpo);
        $posEnero = strpos($cuerpo, '01/' . $hoy->format('Y'));
        $posFebrero = strpos($cuerpo, '02/' . $hoy->format('Y'));
        $posMarzo = strpos($cuerpo, '03/' . $hoy->format('Y'));
        $this->assertNotFalse($posEnero);
        $this->assertNotFalse($posFebrero);
        $this->assertNotFalse($posMarzo);
        $this->assertLessThan($posFebrero, $posEnero);
        $this->assertLessThan($posMarzo, $posFebrero);
    }

    public function testInvoiceFiltersApplyToRowsAndAllSummaryTotals(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'factura.filtros@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Depto filtros', 'ocupado');
        $idServicio = $this->crearServicio(
            $idPropiedad,
            $this->idTipoServicio('gas'),
            'activo',
        );
        $hoy = new DateTimeImmutable('today');
        $primerVencimientoVencido = $hoy->modify('-10 days')->format('Y-m-d');
        $vencimientoVencido = $hoy->modify('-4 days')->format('Y-m-d');

        $this->crearFactura($idServicio, '01', 'pendiente', $hoy->modify('+5 days')->format('Y-m-d'), '1000.00');
        $this->crearFactura(
            $idServicio,
            '02',
            'pendiente',
            $primerVencimientoVencido,
            '2500.00',
            $vencimientoVencido,
        );
        $this->crearFactura($idServicio, '03', 'pagada', $vencimientoVencido, '5000.00');
        $this->iniciarSesion($idPropietario, 'propietario');

        $respuesta = $this->get('/facturas?' . http_build_query([
            'inmueble' => (string) $idPropiedad,
            'servicio' => (string) $idServicio,
            'estado' => 'vencida',
            'desde' => $vencimientoVencido,
            'hasta' => $vencimientoVencido,
        ]));
        $cuerpo = $respuesta->response()->getBody();

        $respuesta->assertOK();
        $this->assertStringContainsString('$ 2.500,00', $cuerpo);
        $this->assertStringNotContainsString('$ 1.000,00', $cuerpo);
        $this->assertStringNotContainsString('$ 5.000,00', $cuerpo);
        $this->assertSame(1, substr_count($cuerpo, '<tr>') - 1);
        $this->assertStringContainsString('value="vencida" selected', $cuerpo);
    }

    public function testAdminSeesAllInvoiceOwnersAndEmptyFilterShowsEmptyState(): void
    {
        $idPropietario = $this->crearUsuario('propietario', 'factura.admin.lista@example.test');
        $idOtroPropietario = $this->crearUsuario('propietario', 'factura.admin.lista.otro@example.test');
        $idPropiedad = $this->crearPropiedad($idPropietario, 'Inmueble visible admin', 'ocupado');
        $idPropiedadAjena = $this->crearPropiedad($idOtroPropietario, 'Otro visible admin', 'ocupado');
        $idServicio = $this->crearServicio(
            $idPropiedad,
            $this->idTipoServicio('telefonia'),
            'activo',
        );
        $idServicioAjeno = $this->crearServicio(
            $idPropiedadAjena,
            $this->idTipoServicio('television'),
            'activo',
        );
        $hoy = new DateTimeImmutable('today');
        $vencimiento = $hoy->modify('+3 days')->format('Y-m-d');
        $this->crearFactura($idServicio, '01', 'pendiente', $vencimiento, '1200.00');
        $this->crearFactura($idServicioAjeno, '01', 'pendiente', $vencimiento, '800.00');
        $idAdmin = $this->crearUsuario('admin', 'factura.admin.lista.admin@example.test');
        $this->iniciarSesion($idAdmin, 'admin');

        $respuesta = $this->get('/facturas');
        $respuesta->assertOK();
        $this->assertStringContainsString('Inmueble visible admin', $respuesta->response()->getBody());
        $this->assertStringContainsString('Otro visible admin', $respuesta->response()->getBody());

        $sinResultados = $this->get('/facturas?' . http_build_query([
            'desde' => '1901-01-01',
            'hasta' => '1901-01-02',
        ]));
        $sinResultados->assertOK();
        $this->assertStringContainsString(
            'No hay facturas registradas',
            $sinResultados->response()->getBody(),
        );
        $this->assertStringContainsString('$ 0,00', $sinResultados->response()->getBody());
    }

    /**
     * @param array<string, string> $sobrescrituras
     * @return array<string, string>
     */
    private function datosFactura(array $sobrescrituras = []): array
    {
        $hoy = new DateTimeImmutable('today');

        return array_merge([
            'periodo' => 'mes',
            'periodo_mes' => $hoy->format('m'),
            'periodo_anio' => $hoy->format('Y'),
            'fecha_emision' => $hoy->format('Y-m-d'),
            'fecha_primer_vencimiento' => $hoy->modify('+10 days')->format('Y-m-d'),
            'fecha_segundo_vencimiento' => '',
            'monto_total' => '1250.50',
            'consumo_del_periodo' => '15',
            'lectura_anterior' => '',
            'lectura_actual' => '',
        ], $sobrescrituras);
    }

    /**
     * @param array<string, string> $datos
     */
    private function postFactura(int $idServicio, array $datos): \CodeIgniter\Test\TestResponse
    {
        $token = $this->tokenFormulario($idServicio);

        return $this->post(
            '/servicios-inmueble/' . $idServicio . '/facturas',
            array_merge($datos, ['csrf_test_name' => $token]),
        );
    }

    private function tokenFormulario(int $idServicio): string
    {
        $formulario = $this->get('/servicios-inmueble/' . $idServicio . '/facturas/nuevo');
        $this->assertSame(200, $formulario->response()->getStatusCode());

        preg_match(
            '/name="csrf_test_name"\s+value="([^"]+)"/',
            $formulario->response()->getBody(),
            $matches,
        );
        $this->assertNotEmpty($matches[1] ?? null, 'No se encontró el token CSRF del formulario.');
        $this->withSession($_SESSION);

        return html_entity_decode($matches[1], ENT_QUOTES);
    }

    private function crearUsuario(string $rol, string $mail): int
    {
        $this->db->table('usuario')->insert([
            'nombre_completo' => 'Usuario de prueba',
            'mail' => $mail,
            'contrasenia' => password_hash('Clave123', PASSWORD_BCRYPT),
            'rol' => $rol,
            'estado' => 'activo',
        ]);

        return (int) $this->db->insertID();
    }

    private function crearPropiedad(int $idPropietario, string $alias, string $estado): int
    {
        $this->db->table('direccion')->insert([
            'calle' => 'Belgrano',
            'numero' => 100,
            'ciudad' => 'Roca',
        ]);
        $idDireccion = (int) $this->db->insertID();
        $idTipoInmueble = (int) $this->db->table('tipo_inmueble')
            ->select('id_tipo_inmueble')
            ->where('nombre', 'casa')
            ->get()
            ->getRow()
            ->id_tipo_inmueble;

        $this->db->table('propiedad')->insert([
            'alias' => $alias,
            'superficie' => '60.00',
            'estado' => $estado,
            'id_tipo_inmueble' => $idTipoInmueble,
            'id_propietario' => $idPropietario,
            'id_direccion' => $idDireccion,
        ]);

        return (int) $this->db->insertID();
    }

    private function crearServicio(
        int $idPropiedad,
        int $idTipo,
        string $estado,
        ?string $medidor = null,
    ): int {
        $this->db->table('propiedad_servicio')->insert([
            'id_propiedad' => $idPropiedad,
            'id_tipo_servicio' => $idTipo,
            'codigo_pago_electronico' => 'CPE-FACT-1',
            'numero_cuenta_contrato' => 'CTA-FACT-1',
            'numero_medidor' => $medidor,
            'domicilio_facturacion' => 'Belgrano 100',
            'titular' => 'Titular de prueba',
            'estado' => $estado,
        ]);

        return (int) $this->db->insertID();
    }

    private function idTipoServicio(string $tipo): int
    {
        return (int) $this->db->table('tipo_servicio')
            ->select('id_tipo_servicio')
            ->where('tipo', $tipo)
            ->where('activo', 1)
            ->get()
            ->getRow()
            ->id_tipo_servicio;
    }

    private function crearFactura(
        int $idServicio,
        string $mes,
        string $estado,
        string $vencimiento,
        string $monto,
        ?string $segundoVencimiento = null,
    ): int {
        $this->db->table('factura')->insert([
            'id_propiedad_servicio' => $idServicio,
            'periodo' => 'mes',
            'periodo_mes' => $mes,
            'periodo_anio' => (new DateTimeImmutable('today'))->format('Y'),
            'fecha_emision' => (new DateTimeImmutable('today'))->format('Y-m-d'),
            'fecha_primer_vencimiento' => $vencimiento,
            'fecha_segundo_vencimiento' => $segundoVencimiento,
            'monto_total' => $monto,
            'consumo_del_periodo' => 0,
            'unidad_medida' => 'kWh',
            'proveedor' => 'Proveedor de prueba',
            'categoria_factura' => 'publico',
            'estado' => $estado,
        ]);

        return (int) $this->db->insertID();
    }

    private function iniciarSesion(int $idUsuario, string $rol): void
    {
        $token = bin2hex(random_bytes(32));
        $this->db->table('sesion')->insert([
            'id_usuario' => $idUsuario,
            'token' => $token,
        ]);

        $this->withSession([
            'id_usuario' => $idUsuario,
            'id_sesion' => (int) $this->db->insertID(),
            'token' => $token,
            'rol' => $rol,
        ]);
    }
}
