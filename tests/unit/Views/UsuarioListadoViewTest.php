<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class UsuarioListadoViewTest extends CIUnitTestCase
{
    public function testUserTableContainsRequiredColumnsAndPreservesFilters(): void
    {
        helper(['auth', 'format', 'url']);
        $html = view('admin/usuarios/index', [
            'titulo' => 'Usuarios',
            'usuarios' => [[
                'id_usuario' => 17,
                'nombre_completo' => 'Juan Pérez',
                'mail' => 'juan@example.test',
                'rol' => 'propietario',
                'estado' => 'activo',
                'fecha_alta' => '2026-11-08 10:30:00',
            ]],
            'filtros' => ['q' => 'Juan', 'rol' => 'propietario', 'estado' => 'activo'],
            'pager' => null,
        ]);

        foreach (['Nombre', 'Correo electrónico', 'Rol', 'Estado', 'Fecha de alta'] as $columna) {
            $this->assertStringContainsString($columna, $html);
        }

        $this->assertStringContainsString('Juan Pérez', $html);
        $this->assertStringContainsString('juan@example.test', $html);
        $this->assertStringContainsString('value="Juan"', $html);
        $this->assertStringContainsString('selected', $html);
    }

    public function testEmptyUserTableShowsTheAcceptanceMessage(): void
    {
        helper(['auth', 'format', 'url']);
        $html = view('admin/usuarios/index', [
            'titulo' => 'Usuarios',
            'usuarios' => [],
            'filtros' => ['q' => '', 'rol' => '', 'estado' => ''],
            'pager' => null,
        ]);

        $this->assertStringContainsString('No se encontraron usuarios.', $html);
    }
}
