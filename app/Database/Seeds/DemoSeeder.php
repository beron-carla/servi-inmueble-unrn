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

        $consumos = [
            2025 => [82, 97, 110, 93, 125, 118, 136, 129, 114, 142, 127, 151],
            2026 => [105, 89, 121, 138, 116, 149, 132, 158, 141, 127, 165, 146],
        ];
        $lecturaActual = 1000;
        $mesActual = new DateTimeImmutable('first day of this month');

        foreach ($consumos as $year => $consumosAnuales) {
            foreach ($consumosAnuales as $index => $consumo) {
                $month = $index + 1;
                $period = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
                $monthNumber = $period->format('m');
                $yearMonth = $period->format('Y-m');
                $lecturaAnterior = $lecturaActual;
                $lecturaActual += $consumo;
                $existingInvoice = $this->db->table('factura')
                    ->where('id_propiedad_servicio', $propertyServiceId)
                    ->where('periodo', 'mes')
                    ->where('periodo_anio', $year)
                    ->where('periodo_mes', $monthNumber)
                    ->get()
                    ->getRowArray();

                if ($existingInvoice !== null) {
                    $invoiceId = (int) $existingInvoice['id_factura'];
                } else {
                    $paid = $period < $mesActual;
                    $monto = $consumo * (120 + ($month % 5) * 4);
                    $this->db->table('factura')->insert([
                        'id_propiedad_servicio' => $propertyServiceId,
                        'periodo' => 'mes',
                        'periodo_mes' => $monthNumber,
                        'periodo_anio' => $year,
                        'fecha_emision' => $period->format('Y-m-01'),
                        'fecha_primer_vencimiento' => $period->setDate($year, $month, 10)->format('Y-m-d'),
                        'fecha_segundo_vencimiento' => null,
                        'monto_total' => number_format($monto, 2, '.', ''),
                        'consumo_del_periodo' => $consumo,
                        'lectura_anterior' => $lecturaAnterior,
                        'lectura_actual' => $lecturaActual,
                        'unidad_medida' => $electricity['unidad_medida'],
                        'proveedor' => $electricity['proveedor_predeterminado'],
                        'categoria_factura' => $electricity['categoria'],
                        'estado' => $paid ? 'pagada' : 'pendiente',
                    ]);
                    $invoiceId = $this->db->insertID();
                }

                if ($period < $mesActual
                    && $this->db->table('transaccion')->where('id_factura', $invoiceId)->countAllResults() === 0
                ) {
                    $fechaPago = $period->setDate($year, $month, 8)->format('Y-m-d');
                    $this->db->table('transaccion')->insert([
                        'id_factura' => $invoiceId,
                        'id_propietario' => $owner['id_usuario'],
                        'fecha_pago' => $fechaPago,
                        'monto_abonado' => $existingInvoice['monto_total'] ?? number_format($monto, 2, '.', ''),
                        'medio_pago' => 'transferencia',
                        'numero_comprobante' => 'DEMO-' . $yearMonth,
                        'comprobante_digital' => null,
                        'en_mora' => 0,
                        'estado' => 'validada',
                        'fecha_validacion' => $fechaPago . ' 12:00:00',
                    ]);
                }
            }
        }
    }
}
