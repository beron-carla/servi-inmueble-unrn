<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $allowedRoles = array_map('strtolower', $arguments ?? []);
        $role = strtolower((string) session()->get('rol'));

        if (! in_array($role, $allowedRoles, true)) {
            return service('response')
                ->setStatusCode(403)
                ->setBody(view('errors/html/error_403'));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
