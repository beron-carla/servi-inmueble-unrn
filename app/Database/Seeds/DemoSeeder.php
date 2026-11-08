<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use DateTimeImmutable;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (ENVIRONMENT === 'production') {
            return;
        }

        $ownerMail = 'propietario@serviinmueble.local';
        $owner = $this->db->table('usuario')->where('mail', $ownerMail)->get()->getRowArray();

        if ($owner === null) {
            $hash = password_hash('Propietario1234', defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT);

            if ($hash === false) {
                throw new RuntimeException('No se pudo generar el hash del usuario de demostración.');
            }

            $this->db->table('usuario')->insert([
                'nombre_completo' => 'Propietario Demo',
                'mail' => $ownerMail,
                'contrasenia' => $hash,
                'rol' => 'propietario',
                'estado' => 'activo',
                'cambiar_contrasenia' => 0,
            ]);
            $owner = $this->db->table('usuario')->where('mail', $ownerMail)->get()->getRowArray();
        }

        $type = $this->db->table('tipo_inmueble')->where('nombre', 'departamento')->get()->getRowArray();
        $electricity = $this->db->table('tipo_servicio')
            ->where('tipo', 'electricidad')
            ->where('proveedor_predeterminado', 'Edenor')
            ->get()
            ->getRowArray();

        if ($owner === null || $type === null || $electricity === null) {
            throw new RuntimeException('No se encontraron los catálogos necesarios para cargar los datos demo.');
        }

        $property = $this->db->table('propiedad')
            ->where('id_propietario', $owner['id_usuario'])
            ->where('alias', 'Depto Centro')
            ->get()
            ->getRowArray();

        if ($property === null) {
            $this->db->table('direccion')->insert([
                'calle' => 'Mitre',
                'numero' => 450,
                'piso' => 2,
                'depto' => 'B',
                'ciudad' => 'General Roca',
            ]);

            $this->db->table('propiedad')->insert([
                'alias' => 'Depto Centro',
                'superficie' => 55.00,
                'estado' => 'disponible',
                'id_tipo_inmueble' => $type['id_tipo_inmueble'],
                'id_propietario' => $owner['id_usuario'],
                'id_direccion' => $this->db->insertID(),
            ]);
            $property = $this->db->table('propiedad')
                ->where('id_propietario', $owner['id_usuario'])
                ->where('alias', 'Depto Centro')
                ->get()
                ->getRowArray();
        }

        if ($property === null) {
            throw new RuntimeException('No se pudo recuperar el inmueble demo.');
        }

        $propertyService = $this->db->table('propiedad_servicio')
            ->where('id_propiedad', $property['id_propiedad'])
            ->where('id_tipo_servicio', $electricity['id_tipo_servicio'])
            ->get()
            ->getRowArray();

        if ($propertyService === null) {
            $this->db->table('propiedad_servicio')->insert([
                'id_propiedad' => $property['id_propiedad'],
                'id_tipo_servicio' => $electricity['id_tipo_servicio'],
                'codigo_pago_electronico' => 'DEMO-EDENOR-01',
                'numero_cuenta_contrato' => 'CTA-DEMO-01',
                'numero_medidor' => null,
                'domicilio_facturacion' => 'Mitre 450, 2 B',
                'titular' => 'Propietario Demo',
                'estado' => 'activo',
            ]);
            $propertyServiceId = $this->db->insertID();
        } else {
            $propertyServiceId = (int) $propertyService['id_propiedad_servicio'];
        }

        $currentMonth = new DateTimeImmutable('first day of this month');

        foreach ([-2, -1, 1] as $index => $monthOffset) {
            $period = $currentMonth->modify(($monthOffset < 0 ? '' : '+') . $monthOffset . ' months');
            $month = $period->format('m');
            $year = $period->format('Y');
            $existingInvoice = $this->db->table('factura')
                ->where('id_propiedad_servicio', $propertyServiceId)
                ->where('periodo', 'mes')
                ->where('periodo_anio', $year)
                ->where('periodo_mes', $month)
                ->get()
                ->getRowArray();

            if ($existingInvoice !== null) {
                $invoiceId = (int) $existingInvoice['id_factura'];
            } else {
                $paid = $index === 0;
                $this->db->table('factura')->insert([
                    'id_propiedad_servicio' => $propertyServiceId,
                    'periodo' => 'mes',
                    'periodo_mes' => $month,
                    'periodo_anio' => $year,
                    'fecha_emision' => $period->format('Y-m-01'),
                    'fecha_primer_vencimiento' => $period->setDate((int) $year, (int) $month, 10)->format('Y-m-d'),
                    'fecha_segundo_vencimiento' => null,
                    'monto_total' => '15000.00',
                    'consumo_del_periodo' => 100,
                    'lectura_anterior' => 1000,
                    'lectura_actual' => 1100,
                    'unidad_medida' => $electricity['unidad_medida'],
                    'proveedor' => $electricity['proveedor_predeterminado'],
                    'categoria_factura' => $electricity['categoria'],
                    'estado' => $paid ? 'pagada' : 'pendiente',
                ]);
                $invoiceId = $this->db->insertID();
            }

            if ($index === 0 && $this->db->table('transaccion')->where('id_factura', $invoiceId)->countAllResults() === 0) {
                $this->db->table('transaccion')->insert([
                    'id_factura' => $invoiceId,
                    'id_propietario' => $owner['id_usuario'],
                    'fecha_pago' => $currentMonth->modify('-1 month')->format('Y-m-d'),
                    'monto_abonado' => '15000.00',
                    'medio_pago' => 'transferencia',
                    'numero_comprobante' => 'DEMO-FACTURA-PAGADA',
                    'comprobante_digital' => null,
                    'en_mora' => 0,
                ]);
            }
        }
    }
}
