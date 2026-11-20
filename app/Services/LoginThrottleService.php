<?php

namespace App\Services;

use App\Repositories\HistoricoLoginRepository;
use RuntimeException;

class LoginThrottleService
{
    private const MAX_INTENTOS = 5;
    private const VENTANA_SEGUNDOS = 900;

    public function __construct(private ?HistoricoLoginRepository $historico = null)
    {
        $this->historico ??= new HistoricoLoginRepository();
    }

    public function estaBloqueado(string $email, string $ip): bool
    {
        $desde = date('Y-m-d H:i:s', time() - self::VENTANA_SEGUNDOS);

        return $this->historico->contarFallosPorEmailDesde($email, $desde) >= self::MAX_INTENTOS
            || $this->historico->contarFallosPorIpDesde($ip, $desde) >= self::MAX_INTENTOS;
    }

    public function registrarIntento(string $email, string $ip, bool $exitoso): void
    {
        if (! $this->historico->registrar($email, $ip, $exitoso)) {
            throw new RuntimeException('No se pudo registrar el intento de inicio de sesión.');
        }
    }
}
