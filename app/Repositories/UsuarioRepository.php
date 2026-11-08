<?php

namespace App\Repositories;

use App\Models\UsuarioModel;

class UsuarioRepository
{
    public function __construct(private ?UsuarioModel $model = null)
    {
        $this->model ??= new UsuarioModel();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorId(int $id): ?array
    {
        return $this->model->find($id) ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorMail(string $mail): ?array
    {
        $mail = mb_strtolower(trim($mail), 'UTF-8');

        return $this->model->where('mail', $mail)->first() ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarPorMailDeOtroUsuario(string $mail, int $idUsuario): ?array
    {
        $mail = mb_strtolower(trim($mail), 'UTF-8');

        return $this->model
            ->where('mail', $mail)
            ->where('id_usuario !=', $idUsuario)
            ->first() ?: null;
    }

    /**
     * @param array<string, mixed> $usuario
     */
    public function crear(array $usuario): bool
    {
        return $this->model->insert($usuario) !== false;
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function actualizar(int $idUsuario, array $datos): bool
    {
        return $this->model->update($idUsuario, $datos) !== false;
    }

    /**
     * @param array{q?: string, rol?: string, estado?: string} $filtros
     * @return array{usuarios: list<array<string, mixed>>, pager: \CodeIgniter\Pager\PagerInterface|null}
     */
    public function listar(array $filtros = [], int $pagina = 1): array
    {
        if (($filtros['q'] ?? '') !== '') {
            $this->model
                ->groupStart()
                ->like('nombre_completo', $filtros['q'])
                ->orLike('mail', $filtros['q'])
                ->groupEnd();
        }

        if (($filtros['rol'] ?? '') !== '') {
            $this->model->where('rol', $filtros['rol']);
        }

        if (($filtros['estado'] ?? '') !== '') {
            $this->model->where('estado', $filtros['estado']);
        }

        $usuarios = $this->model
            ->orderBy('id_usuario', 'DESC')
            ->paginate(20, 'usuarios', $pagina);

        return [
            'usuarios' => $usuarios,
            'pager' => $this->model->pager,
        ];
    }

    public function actualizarContrasenia(int $idUsuario, string $hash, bool $cambiarContrasenia): bool
    {
        return $this->model->update($idUsuario, [
            'contrasenia'         => $hash,
            'cambiar_contrasenia' => $cambiarContrasenia ? 1 : 0,
        ]);
    }
}
