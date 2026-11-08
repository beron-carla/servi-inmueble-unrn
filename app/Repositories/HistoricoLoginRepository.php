<?php

namespace App\Repositories;

use App\Models\HistoricoLoginModel;

class HistoricoLoginRepository
{
    public function __construct(private ?HistoricoLoginModel $model = null)
    {
        $this->model ??= new HistoricoLoginModel();
    }

    public function contarFallosPorEmailDesde(string $email, string $desde): int
    {
        return $this->model
            ->where('email', $email)
            ->where('exitoso', 0)
            ->where('fecha >=', $desde)
            ->countAllResults();
    }

    public function contarFallosPorIpDesde(string $ip, string $desde): int
    {
        return $this->model
            ->where('ip', $ip)
            ->where('exitoso', 0)
            ->where('fecha >=', $desde)
            ->countAllResults();
    }

    public function registrar(string $email, string $ip, bool $exitoso): bool
    {
        return $this->model->insert([
            'email'   => $email,
            'ip'      => $ip,
            'exitoso' => $exitoso ? 1 : 0,
        ]) !== false;
    }
}
