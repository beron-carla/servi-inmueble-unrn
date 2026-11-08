<?php

namespace App\Repositories;

use CodeIgniter\Database\BaseConnection;

class SesionRepository
{
    public function __construct(private ?BaseConnection $db = null)
    {
        $this->db ??= db_connect();
    }

    public function crear(int $idUsuario, string $token): int|false
    {
        if (! $this->db->table('sesion')->insert([
            'id_usuario' => $idUsuario,
            'token'      => $token,
        ])) {
            return false;
        }

        return (int) $this->db->insertID();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarValida(int $idSesion, int $idUsuario, string $token): ?array
    {
        return $this->db->table('sesion')
            ->where('id_sesion', $idSesion)
            ->where('id_usuario', $idUsuario)
            ->where('token', $token)
            ->get()
            ->getRowArray() ?: null;
    }

    public function actualizarActividad(int $idSesion): bool
    {
        return $this->db->table('sesion')
            ->where('id_sesion', $idSesion)
            ->update(['ultima_actividad' => date('Y-m-d H:i:s')]);
    }

    public function eliminar(int $idSesion): bool
    {
        return $this->db->table('sesion')
            ->where('id_sesion', $idSesion)
            ->delete();
    }

    public function eliminarPorUsuario(int $idUsuario): bool
    {
        return $this->db->table('sesion')
            ->where('id_usuario', $idUsuario)
            ->delete();
    }
}
