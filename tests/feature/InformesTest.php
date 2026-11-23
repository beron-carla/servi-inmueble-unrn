<?php

namespace Tests\Feature;

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class InformesTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testInformeRequiereInmuebleYSinDatosMuestraMensajeLiteral(): void
    {
        $propietario = $this->crearUsuario('informe-vacio@example.test', 'propietario');
        $this->iniciarSesion($propietario, 'propietario');

        $respuesta = $this->get('/informes/consumo-interanual');

        $respuesta->assertStatus(200);
        $respuesta->assertSee('No hay datos de consumo para los filtros seleccionados');
    }

    public function testPropietarioSoloVeSuInmuebleYNoPuedeAccederAlAjeno(): void
    {
        $propietario = $this->crearUsuario('informe-propio@example.test', 'propietario');
        $otro = $this->crearUsuario('informe-ajeno@example.test', 'propietario');
        $propia = $this->crearPropiedadYServicio($propietario, 'Inmueble propio', 'kWh');
        $ajena = $this->crearPropiedadYServicio($otro, 'Inmueble ajeno', 'kWh');
        $this->iniciarSesion($propietario, 'propietario');

        $propiaRespuesta = $this->get('/informes/consumo-interanual?inmueble=' . $propia);
        $propiaRespuesta->assertStatus(200);
        $propiaRespuesta->assertSee('Inmueble propio');
        $propiaRespuesta->assertDontSee('Inmueble ajeno');

        $ajenaRespuesta = $this->get('/informes/consumo-interanual?inmueble=' . $ajena);
        $ajenaRespuesta->assertStatus(403);
        $ajenaRespuesta->assertSee('No tiene permisos para esta acción');
    }

    public function testComparaDoceMesesAnualesEnResumenYMonedaFijaUsaMonto(): void
    {
        $admin = $this->crearUsuario('informe-admin@example.test', 'admin');
        [$idPropiedad, $idServicio] = $this->crearPropiedadYServicioConDosIds(
            $admin,
            'Inmueble métrico',
            'Moneda fija',
        );

        $this->db->table('factura')->insert([
            'id_propiedad_servicio' => $idServicio,
            'periodo' => 'mes',
            'periodo_mes' => '01',
            'periodo_anio' => 2025,
            'fecha_emision' => '2025-01-01',
            'fecha_primer_vencimiento' => '2025-01-10',
            'monto_total' => '120.00',
            'consumo_del_periodo' => 0,
            'unidad_medida' => 'Moneda fija',
            'proveedor' => 'Proveedor',
            'categoria_factura' => 'privado',
            'estado' => 'pendiente',
        ]);
        $this->db->table('factura')->insert([
            'id_propiedad_servicio' => $idServicio,
            'periodo' => 'mes',
            'periodo_mes' => '01',
            'periodo_anio' => 2026,
            'fecha_emision' => '2026-01-01',
            'fecha_primer_vencimiento' => '2026-01-10',
            'monto_total' => '150.00',
            'consumo_del_periodo' => 0,
            'unidad_medida' => 'Moneda fija',
            'proveedor' => 'Proveedor',
            'categoria_factura' => 'privado',
            'estado' => 'pendiente',
        ]);
        $this->db->table('factura')->insert([
            'id_propiedad_servicio' => $idServicio,
            'periodo' => 'anio',
            'periodo_mes' => '00',
            'periodo_anio' => 2026,
            'fecha_emision' => '2026-01-01',
            'fecha_primer_vencimiento' => '2026-12-31',
            'monto_total' => '900.00',
            'consumo_del_periodo' => 0,
            'unidad_medida' => 'Moneda fija',
            'proveedor' => 'Proveedor',
            'categoria_factura' => 'privado',
            'estado' => 'pendiente',
        ]);
        $this->iniciarSesion($admin, 'admin');

        $respuesta = $this->get(
            '/informes/consumo-interanual'
            . '?inmueble=' . $idPropiedad
            . '&servicio=' . $idServicio
            . '&anio_base=2026&anio_comparado=2025&unidad=Moneda+fija',
        );

        $respuesta->assertStatus(200);
        $respuesta->assertSee('Monto mensual');
        $respuesta->assertSee('Resumen de facturas anuales');
        $respuesta->assertSee('Enero');
        $respuesta->assertSee('Detalle mensual');
        $respuesta->assertSee('Total');
    }

    public function testMesSinFacturaValeCeroYVariacionConBaseCeroEsGuion(): void
    {
        $propietario = $this->crearUsuario('informe-cero@example.test', 'propietario');
        [$idPropiedad, $idServicio] = $this->crearPropiedadYServicioConDosIds(
            $propietario,
            'Inmueble cero',
            'kWh',
        );
        $this->db->table('factura')->insert([
            'id_propiedad_servicio' => $idServicio,
            'periodo' => 'mes',
            'periodo_mes' => '02',
            'periodo_anio' => 2026,
            'fecha_emision' => '2026-02-01',
            'fecha_primer_vencimiento' => '2026-02-10',
            'monto_total' => '100.00',
            'consumo_del_periodo' => 20,
            'unidad_medida' => 'kWh',
            'proveedor' => 'Proveedor',
            'categoria_factura' => 'publico',
            'estado' => 'pendiente',
        ]);
        $this->iniciarSesion($propietario, 'propietario');

        $respuesta = $this->get(
            '/informes/consumo-interanual'
            . '?inmueble=' . $idPropiedad
            . '&anio_base=2025&anio_comparado=2026&unidad=kWh',
        );

        $respuesta->assertStatus(200);
        $respuesta->assertSee('Enero');
        $respuesta->assertSee('—');
    }

    private function crearUsuario(string $mail, string $rol): int
    {
        $this->db->table('usuario')->insert([
            'nombre_completo' => 'Usuario informe',
            'mail' => $mail,
            'contrasenia' => password_hash('Clave123', PASSWORD_BCRYPT),
            'rol' => $rol,
            'estado' => 'activo',
        ]);

        return (int) $this->db->insertID();
    }

    private function crearPropiedadYServicio(int $idPropietario, string $alias, string $unidad): int
    {
        [$idPropiedad] = $this->crearPropiedadYServicioConDosIds(
            $idPropietario,
            $alias,
            $unidad,
        );

        return $idPropiedad;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function crearPropiedadYServicioConDosIds(
        int $idPropietario,
        string $alias,
        string $unidad,
    ): array {
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
        $idPropiedad = (int) $this->db->insertID();

        $tipoServicio = $this->db->table('tipo_servicio')
            ->select('id_tipo_servicio')
            ->where('unidad_medida', $unidad)
            ->where('activo', 1)
            ->get()
            ->getRow();
        if ($tipoServicio === null) {
            $this->db->table('tipo_servicio')->insert([
                'tipo' => strtolower(str_replace(' ', '_', $unidad)),
                'categoria' => 'privado',
                'unidad_medida' => $unidad,
                'proveedor_predeterminado' => 'Proveedor',
                'activo' => 1,
            ]);
            $idTipoServicio = (int) $this->db->insertID();
        } else {
            $idTipoServicio = (int) $tipoServicio->id_tipo_servicio;
        }

        $this->db->table('propiedad_servicio')->insert([
            'id_propiedad' => $idPropiedad,
            'id_tipo_servicio' => $idTipoServicio,
            'codigo_pago_electronico' => 'CPE-' . $idPropiedad,
            'numero_cuenta_contrato' => 'CTA-' . $idPropiedad,
            'domicilio_facturacion' => 'Mitre 100',
            'titular' => 'Titular',
            'estado' => 'activo',
        ]);
        $idServicio = (int) $this->db->insertID();

        return [$idPropiedad, $idServicio];
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
