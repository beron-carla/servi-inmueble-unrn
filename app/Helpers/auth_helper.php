<?php

if (! function_exists('usuario_actual')) {
    /**
     * @return array<string, mixed>|null
     */
    function usuario_actual(): ?array
    {
        $userId = (int) session()->get('id_usuario');

        if ($userId < 1) {
            return null;
        }

        return db_connect()
            ->table('usuario')
            ->where('id_usuario', $userId)
            ->get()
            ->getRowArray() ?: null;
    }
}

if (! function_exists('es_admin')) {
    function es_admin(): bool
    {
        return (string) session()->get('rol') === 'admin';
    }
}

if (! function_exists('es_propietario')) {
    function es_propietario(): bool
    {
        return (string) session()->get('rol') === 'propietario';
    }
}

if (! function_exists('puede_acceder_propiedad')) {
    function puede_acceder_propiedad(int $idPropietario): bool
    {
        return es_admin() || ((int) session()->get('id_usuario') === $idPropietario && es_propietario());
    }
}
