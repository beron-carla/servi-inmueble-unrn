<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\UsuarioService;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Exceptions\PageNotFoundException;

class UsuariosController extends BaseController
{
    public function __construct(private ?UsuarioService $usuarios = null)
    {
        $this->usuarios ??= new UsuarioService();
    }

    public function index(): string
    {
        $resultado = $this->usuarios->listar([
            'q' => $this->request->getGet('q'),
            'rol' => $this->request->getGet('rol'),
            'estado' => $this->request->getGet('estado'),
            'page' => $this->request->getGet('page'),
        ]);
        $resultado['pager']?->only(['q', 'rol', 'estado']);

        return view('admin/usuarios/index', ['titulo' => 'Usuarios'] + $resultado);
    }

    public function show(int $id): string
    {
        $usuario = $this->usuarios->buscarPorId($id);

        if ($usuario === null) {
            throw PageNotFoundException::forPageNotFound('No se encontró el usuario solicitado.');
        }

        return view('admin/usuarios/show', [
            'titulo' => 'Detalle del usuario',
            'usuario' => $usuario,
            'esUsuarioActual' => (int) session('id_usuario') === $id,
        ]);
    }

    public function edit(int $id): string
    {
        $usuario = $this->usuarios->buscarPorId($id);

        if ($usuario === null) {
            throw PageNotFoundException::forPageNotFound('No se encontró el usuario solicitado.');
        }

        return view('admin/usuarios/edit', [
            'titulo' => 'Editar usuario',
            'usuario' => $usuario,
            'esUsuarioActual' => (int) session('id_usuario') === $id,
            'errores' => session()->getFlashdata('usuario_errores') ?? [],
            'anterior' => session()->getFlashdata('usuario_anterior') ?? [],
        ]);
    }

    public function create(): string
    {
        return view('admin/usuarios/create', [
            'titulo' => 'Alta de usuario',
            'errores' => session()->getFlashdata('usuario_errores') ?? [],
            'anterior' => session()->getFlashdata('usuario_anterior') ?? [],
        ]);
    }

    public function store(): RedirectResponse
    {
        $nombre = $this->postString('nombre_completo');
        $mail = $this->postString('mail');
        $contrasenia = $this->postString('contrasenia');
        $rol = $this->postString('rol');
        $resultado = $this->usuarios->crear($nombre, $mail, $contrasenia, $rol);

        if (! $resultado['exito']) {
            session()->setFlashdata('usuario_errores', $resultado['errores']);
            session()->setFlashdata('usuario_anterior', [
                'nombre_completo' => $nombre,
                'mail' => $mail,
                'rol' => $rol,
            ]);
            session()->setFlashdata('error', reset($resultado['errores']));

            return redirect()->to(route_to('admin.usuarios.create'));
        }

        session()->setFlashdata('success', 'Usuario creado correctamente.');

        return redirect()->to(route_to('admin.usuarios.index'));
    }

    public function update(int $id): RedirectResponse
    {
        $nombre = $this->postString('nombre_completo');
        $mail = $this->postString('mail');
        $rol = $this->postString('rol');
        $estado = $this->postString('estado');
        $resultado = $this->usuarios->actualizar(
            $id,
            (int) session('id_usuario'),
            $nombre,
            $mail,
            $rol,
            $estado,
        );

        if ($resultado['no_encontrado'] ?? false) {
            throw PageNotFoundException::forPageNotFound('No se encontró el usuario solicitado.');
        }

        if (! $resultado['exito']) {
            session()->setFlashdata('usuario_errores', $resultado['errores']);
            session()->setFlashdata('usuario_anterior', [
                'nombre_completo' => $nombre,
                'mail' => $mail,
                'rol' => $rol,
                'estado' => $estado,
            ]);
            session()->setFlashdata(
                'error',
                reset($resultado['errores']) ?: 'No se pudo modificar el usuario.',
            );

            return redirect()->to(route_to('admin.usuarios.edit', $id));
        }

        session()->setFlashdata('success', 'Usuario modificado correctamente.');

        return redirect()->to(route_to('admin.usuarios.show', $id));
    }

    public function block(int $id): RedirectResponse
    {
        return $this->responderCambio(
            $this->usuarios->bloquear($id, (int) session('id_usuario')),
            $id,
            'Usuario bloqueado correctamente.',
        );
    }

    public function deactivate(int $id): RedirectResponse
    {
        return $this->responderCambio(
            $this->usuarios->darDeBaja($id, (int) session('id_usuario')),
            $id,
            'Usuario dado de baja correctamente.',
        );
    }

    public function activate(int $id): RedirectResponse
    {
        return $this->responderCambio(
            $this->usuarios->reactivar($id, (int) session('id_usuario')),
            $id,
            'Usuario reactivado correctamente.',
        );
    }

    public function resetPassword(int $id): RedirectResponse
    {
        $resultado = $this->usuarios->restablecerContrasenia($id, (int) session('id_usuario'));

        if ($resultado['no_encontrado'] ?? false) {
            throw PageNotFoundException::forPageNotFound('No se encontró el usuario solicitado.');
        }

        if (! $resultado['exito']) {
            session()->setFlashdata(
                'error',
                reset($resultado['errores']) ?: 'No se pudo restablecer la contraseña.',
            );

            return redirect()->to(route_to('admin.usuarios.show', $id));
        }

        $mensaje = 'Contraseña temporal: ' . $resultado['contrasenia_temporal']
            . '. Se solicitará el cambio en el próximo inicio de sesión.';
        session()->setFlashdata('success', $mensaje);

        if ((int) session('id_usuario') === $id) {
            session()->remove(['id_usuario', 'id_sesion', 'token', 'rol', 'nombre_completo']);

            return redirect()->to(route_to('auth.login'));
        }

        return redirect()->to(route_to('admin.usuarios.show', $id));
    }

    /**
     * @param array{exito: bool, errores: array<string, string>, no_encontrado?: bool} $resultado
     */
    private function responderCambio(array $resultado, int $id, string $mensajeExito): RedirectResponse
    {
        if ($resultado['no_encontrado'] ?? false) {
            throw PageNotFoundException::forPageNotFound('No se encontró el usuario solicitado.');
        }

        if ($resultado['exito']) {
            session()->setFlashdata('success', $mensajeExito);
        } else {
            session()->setFlashdata(
                'error',
                reset($resultado['errores']) ?: 'No se pudo modificar el usuario.',
            );
        }

        return redirect()->to(route_to('admin.usuarios.show', $id));
    }

    private function postString(string $campo): string
    {
        $valor = $this->request->getPost($campo);

        return is_string($valor) ? $valor : '';
    }
}
