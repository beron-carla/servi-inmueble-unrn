<?php

namespace Tests\Feature;

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class InformesPromedioUnidadTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testAlcancePorRolYPropiedadAjenaDevuelve403(): void
    {
        $propietario = $this->crearUsuario('promedio-propio@example.test', 'propietario');
        $otroPropietario = $this->crearUsuario('promedio-ajeno@example.test', 'propietario');
        $propiedadPropia = $this->crearPropiedad($propietario, 'Inmueble propio');
        $propiedadAjena = $this->crearPropiedad($otroPropietario, 'Inmueble ajeno');
        $servicioPropio = $this->crearServicio($propiedadPropia, 'kWh');
        $servicioAjeno = $this->crearServicio($propiedadAjena, 'kWh');
        $this->crearFactura($servicioPropio, 'mes', 2026, '02', 20, 100);
        $this->crearFactura($servicioAjeno, 'mes', 2026, '02', 80, 100);

        $this->iniciarSesion($propietario, 'propietario');
        $propietarioRespuesta = $this->get('/informes/promedio-unidad');
        $propietarioRespuesta->assertStatus(200);
        $propietarioRespuesta->assertSee('Inmueble propio');
        $propietarioRespuesta->assertDontSee('Inmueble ajeno');

        $respuestaAjena = $this->get(
            '/informes/promedio-unidad?inmueble=' . $propiedadAjena,
        );
        $respuestaAjena->assertStatus(403);
        $respuestaAjena->assertSee('No tiene permisos para esta acción');
        $respuestaAjena->assertSee('Volver al dashboard');

        $admin = $this->crearUsuario('promedio-admin@example.test', 'admin');
        $this->iniciarSesion($admin, 'admin');
        $adminRespuesta = $this->get('/informes/promedio-unidad');
        $adminRespuesta->assertStatus(200);
        $adminRespuesta->assertSee('Inmueble propio');
        $adminRespuesta->assertSee('Inmueble ajeno');
    }

    public function testRangoMensualAgregaConsumosYMontosYSeparaFacturasAnuales(): void
    {
        $admin = $this->crearUsuario('promedio-agregados@example.test', 'admin');
        $propiedad = $this->crearPropiedad($admin, 'Inmueble de métricas');
        $servicioKwh = $this->crearServicio($propiedad, 'kWh');
        $servicioM3 = $this->crearServicio($propiedad, 'm³');
        $servicioFijo = $this->crearServicio($propiedad, 'Moneda fija');

        $this->crearFactura($servicioKwh, 'mes', 2026, '01', 100, 100);
        $this->crearFactura($servicioKwh, 'mes', 2026, '02', 10, 100);
        $this->crearFactura($servicioKwh, 'mes', 2026, '03', 30, 100);
        $this->crearFactura($servicioKwh, 'anio', 2026, '00', 50, 900);
        $this->crearFactura($servicioM3, 'mes', 2026, '02', 4, 100);
        $this->crearFactura($servicioM3, 'mes', 2026, '03', 8, 100);
        $this->crearFactura($servicioFijo, 'mes', 2026, '02', 0, 100);
        $this->crearFactura($servicioFijo, 'mes', 2026, '03', 0, 200);
        $this->crearFactura($servicioFijo, 'anio', 2026, '00', 0, 900);
        $this->iniciarSesion($admin, 'admin');

        $respuesta = $this->get(
            '/informes/promedio-unidad?desde=2026-02&hasta=2026-03',
        );

        $respuesta->assertStatus(200);
        $respuesta->assertSee('Cantidad de facturas');
        $respuesta->assertSee('20,00');
        $respuesta->assertSee('10,00');
        $respuesta->assertSee('30,00');
        $respuesta->assertSee('6,00');
        $respuesta->assertSee('4,00');
        $respuesta->assertSee('8,00');
        $respuesta->assertSee('Monto promedio');
        $respuesta->assertSee('$ 150,00');
        $respuesta->assertSee('Resumen de facturas anuales');
        $respuesta->assertSee('50,00');
        $body = $respuesta->response()->getBody();
        $this->assertStringContainsString('id="grafico-promedio-unidad"', $body);
        $this->assertStringContainsString('data-promedios=', $body);
        $this->assertStringContainsString('value="2026-02"', $body);
        $this->assertStringContainsString('value="2026-03"', $body);
    }

    public function testFiltrosDeInmuebleServicioYUnidadSeAplicanEnServidor(): void
    {
        $admin = $this->crearUsuario('promedio-filtros@example.test', 'admin');
        $propiedad = $this->crearPropiedad($admin, 'Inmueble filtrado');
        $servicioKwh = $this->crearServicio($propiedad, 'kWh');
        $servicioGigas = $this->crearServicio($propiedad, 'Gigas');
        $this->crearFactura($servicioKwh, 'mes', 2026, '02', 15, 100);
        $this->crearFactura($servicioKwh, 'mes', 2026, '03', 15, 100);
        $this->crearFactura($servicioGigas, 'mes', 2026, '02', 40, 100);
        $this->crearFactura($servicioGigas, 'mes', 2026, '03', 80, 100);
        $this->iniciarSesion($admin, 'admin');

        $respuesta = $this->get(
            '/informes/promedio-unidad'
            . '?inmueble=' . $propiedad
            . '&servicio=' . $servicioGigas
            . '&unidad=Gigas'
            . '&desde=2026-02&hasta=2026-03',
        );

        $respuesta->assertStatus(200);
        $respuesta->assertSee('Gigas');
        $respuesta->assertSee('60,00');
        $respuesta->assertSee('40,00');
        $respuesta->assertSee('80,00');
        $body = $respuesta->response()->getBody();
        $this->assertStringContainsString('<td>2</td>', $body);
        $this->assertStringNotContainsString('15,00', $body);
        $this->assertStringContainsString('value="Gigas" selected', $body);
    }

    public function testSinDatosMuestraMensajesLiterales(): void
    {
        $propietario = $this->crearUsuario('promedio-vacio@example.test', 'propietario');
        $this->iniciarSesion($propietario, 'propietario');

        $respuesta = $this->get('/informes/promedio-unidad');

        $respuesta->assertStatus(200);
        $respuesta->assertSee('No hay datos para los filtros seleccionados');
        $respuesta->assertSee('Sin datos');
    }

    public function testRolInvalidoDevuelve403(): void
    {
        $usuario = $this->crearUsuario('promedio-rol-invalido@example.test', 'propietario');
        $this->iniciarSesion($usuario, 'otro');

        $respuesta = $this->get('/informes/promedio-unidad');

        $respuesta->assertStatus(403);
        $respuesta->assertSee('No tiene permisos para esta acción');
    }

    private function crearUsuario(string $mail, string $rol): int
    {
        $this->db->table('usuario')->insert([
            'nombre_completo' => 'Usuario de informe',
            'mail' => $mail,
            'contrasenia' => password_hash('Clave123', PASSWORD_BCRYPT),
            'rol' => $rol,
            'estado' => 'activo',
        ]);

        return (int) $this->db->insertID();
    }

    private function crearPropiedad(int $idPropietario, string $alias): int
    {
        $this->db->table('direccion')->insert([
            'calle' => 'Mitre',
            'numero' => 100,
            'ciudad' => 'Roca',
        ]);
        $idDireccion = (int) $this->db->insertID();
        $tipoInmueble = $this->db->table('tipo_inmueble')
            ->select('id_tipo_inmueble')
            ->where('nombre', 'casa')
            ->get()
            ->getRow();

        $this->db->table('propiedad')->insert([
            'alias' => $alias,
            'superficie' => 50,
            'estado' => 'ocupado',
            'id_tipo_inmueble' => (int) $tipoInmueble->id_tipo_inmueble,
            'id_propietario' => $idPropietario,
            'id_direccion' => $idDireccion,
        ]);

        return (int) $this->db->insertID();
    }

    private function crearServicio(int $idPropiedad, string $unidad): int
    {
        $tipo = [
            'kWh' => 'electricidad',
            'm³' => 'agua',
            'Gigas' => 'internet',
            'Moneda fija' => 'impuesto',
        ][$unidad];
        $tipoServicio = $this->db->table('tipo_servicio')
            ->select('id_tipo_servicio')
            ->where('tipo', $tipo)
            ->where('unidad_medida', $unidad)
            ->where('activo', 1)
            ->get()
            ->getRow();

        $this->db->table('propiedad_servicio')->insert([
            'id_propiedad' => $idPropiedad,
            'id_tipo_servicio' => (int) $tipoServicio->id_tipo_servicio,
            'codigo_pago_electronico' => 'CPE-' . random_int(100000, 999999),
            'numero_cuenta_contrato' => 'CTA-' . random_int(100000, 999999),
            'domicilio_facturacion' => 'Mitre 100',
            'titular' => 'Titular',
            'estado' => 'activo',
        ]);

        return (int) $this->db->insertID();
    }

    private function crearFactura(
        int $idServicio,
        string $periodo,
        int $anio,
        string $mes,
        int $consumo,
        int $monto,
    ): void {
        $unidad = $this->db->table('propiedad_servicio')
            ->select('tipo_servicio.unidad_medida, tipo_servicio.categoria, tipo_servicio.proveedor_predeterminado')
            ->join(
                'tipo_servicio',
                'tipo_servicio.id_tipo_servicio = propiedad_servicio.id_tipo_servicio',
            )
            ->where('id_propiedad_servicio', $idServicio)
            ->get()
            ->getRow();
        $mesFecha = $mes === '00' ? '01' : $mes;

        $this->db->table('factura')->insert([
            'id_propiedad_servicio' => $idServicio,
            'periodo' => $periodo,
            'periodo_mes' => $mes,
            'periodo_anio' => $anio,
            'fecha_emision' => sprintf('%04d-%02d-01', $anio, (int) $mesFecha),
            'fecha_primer_vencimiento' => sprintf('%04d-%02d-15', $anio, (int) $mesFecha),
            'monto_total' => number_format((float) $monto, 2, '.', ''),
            'consumo_del_periodo' => $consumo,
            'unidad_medida' => $unidad->unidad_medida,
            'proveedor' => $unidad->proveedor_predeterminado,
            'categoria_factura' => $unidad->categoria,
            'estado' => 'pendiente',
        ]);
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
