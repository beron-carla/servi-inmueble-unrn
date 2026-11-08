<?php

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class AuthRegistrationTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testRegistrationFormIsGuestAccessibleAndDoesNotExposeRoleSelection(): void
    {
        $resultado = $this->get('/auth/registro');

        $resultado->assertOK();
        $resultado->assertSee('Acepto los términos y condiciones');
        $resultado->assertSee('Registrarse');
        $this->assertStringNotContainsString('name="rol"', $resultado->response()->getBody());
    }
}
