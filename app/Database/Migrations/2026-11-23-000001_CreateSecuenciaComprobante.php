<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSecuenciaComprobante extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'fecha' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'ultimo' => [
                'type' => 'INT',
                'unsigned' => true,
                'null' => false,
                'default' => 0,
            ],
        ]);
        $this->forge->addKey('fecha', true);
        $this->forge->createTable('secuencia_comprobante', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('secuencia_comprobante', true);
    }
}
