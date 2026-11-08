<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TipoInmuebleSeeder::class);
        $this->call(TipoServicioSeeder::class);
        $this->call(AdminUserSeeder::class);

        if (ENVIRONMENT !== 'production') {
            $this->call(DemoSeeder::class);
        }
    }
}
