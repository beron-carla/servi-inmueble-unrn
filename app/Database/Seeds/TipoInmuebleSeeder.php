<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TipoInmuebleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['casa', 'departamento', 'local comercial', 'oficina', 'galpon'] as $nombre) {
            $exists = $this->db->table('tipo_inmueble')->where('nombre', $nombre)->countAllResults();

            if ($exists === 0) {
                $this->db->table('tipo_inmueble')->insert(['nombre' => $nombre]);
            }
        }
    }
}
