<?php

namespace Tests\Feature;

use App\Database\Seeds\DatabaseSeeder;
use App\Services\MoraService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class MoraTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testDiasMoraUsesSecondVencimientoAndMinimumZero(): void
    {
        $service = new MoraService();

        $factura = [
            'fecha_primer_vencimiento' => '2026-11-01',
            'fecha_segundo_vencimiento' => '2026-11-10',
        ];

        $this->assertSame('2026-11-10', $service->vencimientoAplicable($factura));
        $this->assertSame(12, $service->diasMora('2026-11-10', '2026-11-22'));
        $this->assertSame(0, $service->diasMora('2026-11-22', '2026-11-22'));
    }

    public function testMoraRouteIsProtectedAndOwnerOnlySeesOwnMora(): void
    {
        $propietario = $this->crearUsuario('mora-propia@example.test', 'propietario');
        $otro = $this->crearUsuario('mora-ajena@example.test', 'propietario');

        $this->crearFacturaMora($propietario, 'Propiedad propia');
        $this->crearFacturaMora($otro, 'Propiedad ajena');
        $this->iniciarSesion($propietario, 'propietario');

        $respuesta = $this->get('/mora');
        $respuesta->assertStatus(200);
        $respuesta->assertSee('Propiedad propia');
        $respuesta->assertDontSee('Propiedad ajena');

        $ajena = $this->get('/mora?propietario=' . $otro . '&inmueble=0');
        $ajena->assertStatus(200);
        $ajena->assertSee('Propiedad propia');
        $ajena->assertDontSee('Propiedad ajena');
    }

    public function testEmptyMoraShowsLiteralMessage(): void
    {
        $propietario = $this->crearUsuario('mora-vacia@example.test', 'propietario');
        $this->crearFacturaNoVencida($propietario);
        $this->iniciarSesion($propietario, 'propietario');

        $respuesta = $this->get('/mora');
        $respuesta->assertStatus(200);
        $respuesta->assertSee('No hay facturas en mora con los filtros seleccionados');
    }

    private function crearUsuario(string $mail, string $rol): int
    {
        $this->db->table('usuario')->insert([
            'nombre_completo' => 'Usuario mora',
            'mail' => $mail,
            'contrasenia' => password_hash('Clave123', PASSWORD_BCRYPT),
            'rol' => $rol,
            'estado' => 'activo',
        ]);
        return (int) $this->db->insertID();
    }

    private function crearFacturaMora(int $propietario, string $alias): int
    {
        $idDireccion = $this->insertarDireccion();
        $idTipoInmueble = (int) $this->db->table('tipo_inmueble')
            ->select('id_tipo_inmueble')
            ->where('nombre', 'casa')
            ->get()
            ->getRow()->id_tipo_inmueble;

        $this->db->table('propiedad')->insert([
            'alias' => $alias,
            'superficie' => 50,
            'estado' => 'ocupado',
            'id_tipo_inmueble' => $idTipoInmueble,
            'id_propietario' => $propietario,
            'id_direccion' => $idDireccion,
        ]);
        $idPropiedad = (int) $this->db->insertID();

        $tipo = $this->db->table('tipo_servicio')
            ->select('id_tipo_servicio')
            ->where('activo', 1)
            ->get()
            ->getRow();
        $this->db->table('propiedad_servicio')->insert([
            'id_propiedad' => $idPropiedad,
            'id_tipo_servicio' => (int) $tipo->id_tipo_servicio,
            'codigo_pago_electronico' => 'CPE-MORA',
            'numero_cuenta_contrato' => 'CTA-MORA',
            'domicilio_facturacion' => 'Mitre 100',
            'titular' => 'Titular',
            'estado' => 'activo',
        ]);
        $idServicio = (int) $this->db->insertID();

        $this->db->table('factura')->insert([
            'id_propiedad_servicio' => $idServicio,
            'periodo' => 'mes',
            'periodo_mes' => '10',
            'periodo_anio' => 2026,
            'fecha_emision' => '2026-10-01',
            'fecha_primer_vencimiento' => '2026-10-10',
            'fecha_segundo_vencimiento' => '2026-10-15',
            'monto_total' => '999.99',
            'consumo_del_periodo' => 10,
            'unidad_medida' => 'Moneda fija',
            'proveedor' => 'Proveedor',
            'categoria_factura' => 'privado',
            'estado' => 'pendiente',
        ]);
        return (int) $this->db->insertID();
    }

    private function crearFacturaNoVencida(int $propietario): void
    {
        $idDireccion = $this->insertarDireccion();
        $idTipoInmueble = (int) $this->db->table('tipo_inmueble')
            ->select('id_tipo_inmueble')
            ->where('nombre', 'casa')
            ->get()
            ->getRow()->id_tipo_inmueble;
        $this->db->table('propiedad')->insert([
            'alias' => 'Propiedad al dia',
            'superficie' => 50,
            'estado' => 'ocupado',
            'id_tipo_inmueble' => $idTipoInmueble,
            'id_propietario' => $propietario,
            'id_direccion' => $idDireccion,
        ]);
        $idPropiedad = (int) $this->db->insertID();
        $tipo = $this->db->table('tipo_servicio')
            ->select('id_tipo_servicio')
            ->where('activo', 1)
            ->get()
            ->getRow();
        $this->db->table('propiedad_servicio')->insert([
            'id_propiedad' => $idPropiedad,
            'id_tipo_servicio' => (int) $tipo->id_tipo_servicio,
            'codigo_pago_electronico' => 'CPE-ALDIA',
            'numero_cuenta_contrato' => 'CTA-ALDIA',
            'domicilio_facturacion' => 'Mitre 100',
            'titular' => 'Titular',
            'estado' => 'activo',
        ]);
        $idServicio = (int) $this->db->insertID();
        $this->db->table('factura')->insert([
            'id_propiedad_servicio' => $idServicio,
            'periodo' => 'mes',
            'periodo_mes' => '11',
            'periodo_anio' => 2026,
            'fecha_emision' => '2026-11-01',
            'fecha_primer_vencimiento' => '2099-12-31',
            'fecha_segundo_vencimiento' => null,
            'monto_total' => '100.00',
            'consumo_del_periodo' => 10,
            'unidad_medida' => 'Moneda fija',
            'proveedor' => 'Proveedor',
            'categoria_factura' => 'privado',
            'estado' => 'pendiente',
        ]);
    }

    private function insertarDireccion(): int
    {
        $this->db->table('direccion')->insert([
            'calle' => 'Mitre',
            'numero' => 100,
            'ciudad' => 'Roca',
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
