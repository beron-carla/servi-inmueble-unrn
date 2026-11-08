<?php

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim((string) env('SEED_ADMIN_EMAIL', 'admin@serviinmueble.local')));
        $password = (string) env('SEED_ADMIN_PASSWORD', 'Admin1234');

        if ($email === '' || $password === '') {
            throw new RuntimeException('SEED_ADMIN_EMAIL y SEED_ADMIN_PASSWORD deben estar configurados.');
        }

        if ($this->db->table('usuario')->where('mail', $email)->countAllResults() > 0) {
            return;
        }

        $algorithm = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        $hash = password_hash($password, $algorithm);

        if ($hash === false) {
            throw new RuntimeException('No se pudo generar el hash de la contraseña del administrador.');
        }

        $this->db->table('usuario')->insert([
            'nombre_completo' => 'Administrador',
            'mail' => $email,
            'contrasenia' => $hash,
            'rol' => 'admin',
            'estado' => 'activo',
            'cambiar_contrasenia' => 1,
        ]);

        if (ENVIRONMENT !== 'testing') {
            CLI::write(
                "Administrador inicial creado ({$email}). Contraseña temporal: {$password}. Guardala; no volverá a mostrarse.",
                'yellow',
            );
        }
    }
}
