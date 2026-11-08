<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TipoServicioSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = [
            ['tipo' => 'agua', 'categoria' => 'publico', 'unidad_medida' => 'm³', 'proveedor_predeterminado' => 'Aguas Rionegrinas'],
            ['tipo' => 'gas', 'categoria' => 'publico', 'unidad_medida' => 'm³', 'proveedor_predeterminado' => 'Camuzzi'],
            ['tipo' => 'electricidad', 'categoria' => 'publico', 'unidad_medida' => 'kWh', 'proveedor_predeterminado' => 'Edenor'],
            ['tipo' => 'internet', 'categoria' => 'privado', 'unidad_medida' => 'Gigas', 'proveedor_predeterminado' => 'Proveedor de internet'],
            ['tipo' => 'telefonia', 'categoria' => 'privado', 'unidad_medida' => 'Gigas', 'proveedor_predeterminado' => 'Proveedor telefónico'],
            ['tipo' => 'television', 'categoria' => 'privado', 'unidad_medida' => 'Gigas', 'proveedor_predeterminado' => 'Proveedor de televisión'],
            ['tipo' => 'impuesto', 'categoria' => 'publico', 'unidad_medida' => 'Moneda fija', 'proveedor_predeterminado' => 'Municipalidad'],
            ['tipo' => 'expensas', 'categoria' => 'privado', 'unidad_medida' => 'Moneda fija', 'proveedor_predeterminado' => 'Administración del edificio'],
            ['tipo' => 'otro', 'categoria' => 'privado', 'unidad_medida' => 'Moneda fija', 'proveedor_predeterminado' => 'Proveedor varios'],
        ];

        foreach ($catalogo as $tipo) {
            $exists = $this->db->table('tipo_servicio')
                ->where('tipo', $tipo['tipo'])
                ->where('proveedor_predeterminado', $tipo['proveedor_predeterminado'])
                ->countAllResults();

            if ($exists === 0) {
                $tipo['activo'] = 1;
                $this->db->table('tipo_servicio')->insert($tipo);
            }
        }
    }
}
