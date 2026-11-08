<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class Home extends BaseController
{
    public function index(): RedirectResponse
    {
        $destino = session()->get('id_usuario') ? 'dashboard' : 'auth/login';

        return redirect()->to(site_url($destino));
    }
}
