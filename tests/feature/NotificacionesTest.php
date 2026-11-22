<?php

namespace Tests\Feature;

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class NotificacionesTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';
    protected $namespace = 'App';
    protected $seed = DatabaseSeeder::class;

    public function testOwnerCanListMarkAndReadEmptyNotifications(): void
    {
        $idUsuario = $this->crearUsuario('propietario');
        $idFactura = $this->crearFactura($idUsuario);
        $idNotificacion = $this->crearNotificacion($idUsuario, $idFactura);
        $this->iniciarSesion($idUsuario, 'propietario');

        $respuesta = $this->get('/notificaciones');
        $respuesta->assertStatus(200);
        $respuesta->assertSee('Ver factura');

        $this->postConCsrf('/notificaciones/' . $idNotificacion . '/leer')
            ->assertRedirectTo('/notificaciones');
        $this->assertSame(1, (int) $this->db->table('notificacion')
            ->where('id_notificacion', $idNotificacion)
            ->get()
            ->getRow()
            ->leida);

        $this->db->table('notificacion')->truncate();
        $respuesta = $this->get('/notificaciones');
        $respuesta->assertSee('No tenés notificaciones');
    }

    public function testAdminHasGlobalReadOnlyListAndOwnerCannotAccessAnotherNotification(): void
    {
        $idPropietario = $this->crearUsuario('propietario');
        $idOtro = $this->crearUsuario('propietario');
        $idFactura = $this->crearFactura($idPropietario);
        $idNotificacion = $this->crearNotificacion($idPropietario, $idFactura);

        $this->iniciarSesion($idOtro, 'propietario');
        $this->postConCsrf('/notificaciones/' . $idNotificacion . '/leer')->assertStatus(403);

        $idAdmin = $this->crearUsuario('admin');
        $this->iniciarSesion($idAdmin, 'admin');
        $this->get('/notificaciones')->assertStatus(200);
        $this->postConCsrf('/notificaciones/leer-todas')->assertStatus(403);
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
            'nombre_completo' => 'Usuario prueba',
            'rol' => $rol,
        ]);
    }

    private function postConCsrf(string $uri): \CodeIgniter\Test\TestResponse
    {
        $pagina = $this->get('/notificaciones');
        $pagina->assertStatus(200);
        preg_match(
            '/name="csrf_test_name"\s+value="([^"]+)"/',
            $pagina->response()->getBody(),
            $matches,
        );
        $this->assertNotEmpty($matches[1] ?? null);
        $this->withSession($_SESSION);

        return $this->post($uri, [
            'csrf_test_name' => html_entity_decode($matches[1], ENT_QUOTES),
        ]);
    }

    private function crearUsuario(string $rol): int
    {
        $this->db->table('usuario')->insert([
            'nombre_completo' => 'Usuario notificación',
            'mail' => 'notif-feature-' . bin2hex(random_bytes(4)) . '@example.test',
            'contrasenia' => password_hash('Clave123!', PASSWORD_DEFAULT),
            'rol' => $rol,
            'estado' => 'activo',
            'fecha_alta' => '2026-11-22',
            'cambiar_contrasenia' => 0,
        ]);

        return (int) $this->db->insertID();
    }

    private function crearFactura(int $idPropietario): int
    {
        $this->db->table('direccion')->insert(['calle' => 'Test', 'numero' => '12', 'ciudad' => 'Roca']);
        $idDireccion = (int) $this->db->insertID();
        $idTipoInmueble = (int) $this->db->table('tipo_inmueble')->select('id_tipo_inmueble')->get()->getRow()->id_tipo_inmueble;
        $this->db->table('propiedad')->insert([
            'alias' => 'Propiedad feature',
            'superficie' => 60,
            'estado' => 'ocupado',
            'id_tipo_inmueble' => $idTipoInmueble,
            'id_propietario' => $idPropietario,
            'id_direccion' => $idDireccion,
        ]);
        $idPropiedad = (int) $this->db->insertID();
        $tipo = $this->db->table('tipo_servicio')->select('id_tipo_servicio')->where('activo', 1)->get()->getRow();
        $this->db->table('propiedad_servicio')->insert([
            'id_propiedad' => $idPropiedad,
            'id_tipo_servicio' => (int) $tipo->id_tipo_servicio,
            'codigo_pago_electronico' => 'CPE-FEATURE',
            'numero_cuenta_contrato' => 'CTA-FEATURE',
            'domicilio_facturacion' => 'Test 12',
            'titular' => 'Titular Feature',
            'estado' => 'activo',
        ]);
        $idServicio = (int) $this->db->insertID();
        $this->db->table('factura')->insert([
            'id_propiedad_servicio' => $idServicio,
            'periodo' => 'mes',
            'periodo_mes' => '11',
            'periodo_anio' => 2026,
            'fecha_emision' => '2026-11-01',
            'fecha_primer_vencimiento' => '2026-11-29',
            'fecha_segundo_vencimiento' => null,
            'monto_total' => '100.00',
            'consumo_del_periodo' => 10,
            'unidad_medida' => 'kWh',
            'proveedor' => 'Test',
            'categoria_factura' => 'Publico',
            'estado' => 'pendiente',
        ]);

        return (int) $this->db->insertID();
    }

    private function crearNotificacion(int $idUsuario, int $idFactura): int
    {
        $this->db->table('notificacion')->insert([
            'id_usuario' => $idUsuario,
            'id_factura' => $idFactura,
            'id_transaccion' => null,
            'tipo' => 'por_vencer',
            'mensaje' => 'La factura correspondiente vence dentro de los próximos 7 días',
            'leida' => 0,
            'fecha_creacion' => '2026-11-22 12:00:00',
            'idempotencia_key' => 'venc:' . $idFactura . ':por_vencer',
        ]);

        return (int) $this->db->insertID();
    }
}
