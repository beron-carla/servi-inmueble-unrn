<?php

namespace App\Services;

use CodeIgniter\Email\Email;

class EmailService
{
    public function __construct(private ?Email $email = null)
    {
        $this->email ??= service('email');
    }

    public function enviarBienvenida(string $destinatario, string $nombre): bool
    {
        helper('url');
        $config = config('Email');
        $loginUrl = site_url('auth/login');

        $this->email->setFrom($config->fromEmail, $config->fromName);
        $this->email->setTo($destinatario);
        $this->email->setSubject('Bienvenido/a a ServiInmueble');
        $this->email->setMessage(view('emails/bienvenida', [
            'nombre' => $nombre,
            'mail' => $destinatario,
            'loginUrl' => $loginUrl,
        ]));

        return $this->email->send();
    }
}
