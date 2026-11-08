<?php

namespace App\Controllers;

use App\Services\AuthService;
use CodeIgniter\HTTP\RedirectResponse;
use Config\Session;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLogin(): string
    {
        return view('auth/login', ['titulo' => 'Iniciar sesión']);
    }

    public function login(): RedirectResponse
    {
        $resultado = $this->authService->iniciarSesion(
            $this->postString('mail'),
            $this->postString('contrasenia'),
            $this->request->getIPAddress(),
        );

        if (! $resultado['exito']) {
            session()->setFlashdata('error', $resultado['mensaje']);
            session()->setFlashdata('login_mail', $this->postString('mail'));

            return redirect()->to(route_to('auth.login'));
        }

        if ($resultado['cambiar_contrasenia']) {
            return redirect()->to(route_to('auth.password.edit'));
        }

        return redirect()->to(route_to('dashboard'));
    }

    public function logout(): RedirectResponse
    {
        $this->authService->cerrarSesion();

        $response = redirect()->to(route_to('auth.login'));
        $response->deleteCookie(config(Session::class)->cookieName);

        return $response;
    }

    public function showRegister(): string
    {
        return view('auth/register', ['titulo' => 'Crear cuenta']);
    }

    public function register(): RedirectResponse
    {
        $nombre = $this->postString('nombre_completo');
        $mail = $this->postString('mail');
        $contrasenia = $this->postString('contrasenia');
        $aceptoTerminos = $this->postString('acepta_terminos') === '1';

        $resultado = $this->authService->registrar($nombre, $mail, $contrasenia, $aceptoTerminos);

        if (! $resultado['exito']) {
            session()->setFlashdata('registro_errores', $resultado['errores']);
            session()->setFlashdata('registro_anterior', [
                'nombre_completo'  => $nombre,
                'mail'             => $mail,
                'acepta_terminos'  => $aceptoTerminos,
            ]);

            $primerError = reset($resultado['errores']);
            session()->setFlashdata('error', $primerError);

            return redirect()->to(route_to('auth.register'));
        }

        session()->setFlashdata('success', 'Registro exitoso');

        if (! $resultado['correo_enviado']) {
            session()->setFlashdata(
                'warning',
                'La cuenta se creó, pero no se pudo enviar el correo de bienvenida. Contacte al administrador.'
            );
        }

        return redirect()->to(route_to('auth.login'));
    }

    public function showChangePassword(): string
    {
        return view('auth/change_password', ['titulo' => 'Cambiar contraseña']);
    }

    public function changePassword(): RedirectResponse
    {
        $resultado = $this->authService->cambiarContrasenia(
            (int) session()->get('id_usuario'),
            $this->postString('contrasenia'),
        );

        if (! $resultado['exito']) {
            session()->setFlashdata('error', $resultado['mensaje']);

            return redirect()->to(route_to('auth.password.edit'));
        }

        session()->setFlashdata('success', 'La contraseña se actualizó correctamente.');

        return redirect()->to(route_to('dashboard'));
    }

    private function postString(string $campo): string
    {
        $valor = $this->request->getPost($campo);

        return is_string($valor) ? $valor : '';
    }
}
