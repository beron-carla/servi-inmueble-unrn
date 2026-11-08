<?php

if (! function_exists('formato_moneda')) {
    function formato_moneda(float $monto): string
    {
        return '$ ' . number_format($monto, 2, ',', '.');
    }
}

if (! function_exists('formato_fecha')) {
    function formato_fecha(?string $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return '—';
        }

        $date = date_create($fecha);

        return $date === false ? $fecha : $date->format('d/m/Y');
    }
}

if (! function_exists('etiqueta_rol')) {
    function etiqueta_rol(string $rol): string
    {
        return ['admin' => 'Administrador', 'propietario' => 'Propietario'][$rol] ?? $rol;
    }
}

if (! function_exists('etiqueta_estado_usuario')) {
    function etiqueta_estado_usuario(string $estado): string
    {
        return ['activo' => 'Activo', 'bloqueado' => 'Bloqueado', 'baja' => 'Dado de baja'][$estado] ?? $estado;
    }
}

if (! function_exists('etiqueta_estado_propiedad')) {
    function etiqueta_estado_propiedad(string $estado): string
    {
        return [
            'ocupado' => 'Ocupado',
            'disponible' => 'Disponible',
            'en mantenimiento' => 'En mantenimiento',
            'inactivo' => 'Inactivo',
        ][$estado] ?? $estado;
    }
}

if (! function_exists('etiqueta_estado_servicio')) {
    function etiqueta_estado_servicio(string $estado): string
    {
        return [
            'activo' => 'Activo',
            'pausado' => 'Pausado',
            'en revision' => 'En revisión',
            'inactivo' => 'Inactivo',
        ][$estado] ?? $estado;
    }
}

if (! function_exists('etiqueta_tipo_servicio')) {
    function etiqueta_tipo_servicio(string $tipo): string
    {
        return [
            'agua' => 'Agua',
            'gas' => 'Gas',
            'electricidad' => 'Electricidad',
            'internet' => 'Internet',
            'telefonia' => 'Telefonía',
            'television' => 'Televisión',
            'impuesto' => 'Impuesto',
            'expensas' => 'Expensas',
            'otro' => 'Otro',
        ][$tipo] ?? $tipo;
    }
}

if (! function_exists('etiqueta_unidad')) {
    function etiqueta_unidad(string $unidad): string
    {
        return $unidad;
    }
}

if (! function_exists('etiqueta_tipo_inmueble')) {
    function etiqueta_tipo_inmueble(string $tipo): string
    {
        return ['galpon' => 'Galpón'][$tipo] ?? ucfirst($tipo);
    }
}
