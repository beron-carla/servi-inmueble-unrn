<?php

namespace App\Services;

final class ComprobanteService
{
    public const MAXIMO_BYTES = 5 * 1024 * 1024;

    /**
     * @param array<string, mixed>|null $archivo
     * @return array{nombre: string, mime: string, contenido: string}|null
     */
    public function validar(?array $archivo, string $medioPago): ?array
    {
        if ($archivo === null
            || (
                ($archivo['tmp_name'] ?? '') === ''
                && ! isset($archivo['contenido'])
            )
        ) {
            return null;
        }

        $tamano = (int) ($archivo['size'] ?? 0);
        if ($tamano > self::MAXIMO_BYTES) {
            return null;
        }

        $ruta = (string) ($archivo['tmp_name'] ?? '');
        $contenido = is_string($archivo['contenido'] ?? null)
            ? (string) $archivo['contenido']
            : (is_file($ruta) ? (string) file_get_contents($ruta) : '');

        if ($contenido === '') {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->buffer($contenido);

        if (str_starts_with($contenido, '%PDF-')) {
            $mime = 'application/pdf';
        } elseif (strlen($contenido) >= 3
            && ord($contenido[0]) === 0xFF
            && ord($contenido[1]) === 0xD8
            && ord($contenido[2]) === 0xFF
        ) {
            $mime = 'image/jpeg';
        }

        if (! in_array($mime, ['application/pdf', 'image/jpeg'], true)) {
            return null;
        }

        return [
            'nombre' => (string) ($archivo['name'] ?? 'comprobante'),
            'mime' => $mime,
            'contenido' => $contenido,
        ];
    }
}
