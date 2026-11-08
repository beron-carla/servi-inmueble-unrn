<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', 'Home::index', ['as' => 'home']);

$routes->group('auth', ['filter' => 'guest'], static function (RouteCollection $routes): void {
    $routes->get('login', 'AuthController::showLogin', ['as' => 'auth.login']);
    $routes->post('login', 'AuthController::login', ['as' => 'auth.login.attempt']);
    $routes->get('registro', 'AuthController::showRegister', ['as' => 'auth.register']);
    $routes->post('registro', 'AuthController::register', ['as' => 'auth.register.store']);
});

$routes->group('auth', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->post('logout', 'AuthController::logout', ['as' => 'auth.logout']);
    $routes->get('cambiar-contrasenia', 'AuthController::showChangePassword', ['as' => 'auth.password.edit']);
    $routes->post('cambiar-contrasenia', 'AuthController::changePassword', ['as' => 'auth.password.update']);
});

$routes->get('dashboard', 'DashboardController::index', ['filter' => 'auth', 'as' => 'dashboard']);

$routes->get('inmuebles', 'InmueblesController::index', ['filter' => 'auth', 'as' => 'inmuebles.index']);
$routes->get('inmuebles/nuevo', 'InmueblesController::create', ['filter' => 'auth', 'as' => 'inmuebles.create']);
$routes->post('inmuebles', 'InmueblesController::store', ['filter' => 'auth', 'as' => 'inmuebles.store']);
$routes->get('inmuebles/(:num)', 'InmueblesController::show/$1', ['filter' => 'auth', 'as' => 'inmuebles.show']);
$routes->get('inmuebles/(:num)/editar', 'InmueblesController::edit/$1', ['filter' => 'auth', 'as' => 'inmuebles.edit']);
$routes->post('inmuebles/(:num)', 'InmueblesController::update/$1', ['filter' => 'auth', 'as' => 'inmuebles.update']);
$routes->post('inmuebles/(:num)/baja', 'InmueblesController::deactivate/$1', ['filter' => 'auth', 'as' => 'inmuebles.deactivate']);
$routes->post('inmuebles/(:num)/reactivar', 'InmueblesController::activate/$1', ['filter' => 'auth', 'as' => 'inmuebles.activate']);

$routes->get('inmuebles/(:num)/servicios/nuevo', 'ServiciosInmuebleController::create/$1', ['filter' => 'auth', 'as' => 'servicios.create']);
$routes->post('inmuebles/(:num)/servicios', 'ServiciosInmuebleController::store/$1', ['filter' => 'auth', 'as' => 'servicios.store']);
$routes->get('servicios-inmueble/(:num)', 'ServiciosInmuebleController::show/$1', ['filter' => 'auth', 'as' => 'servicios.show']);
$routes->get('servicios-inmueble/(:num)/editar', 'ServiciosInmuebleController::edit/$1', ['filter' => 'auth', 'as' => 'servicios.edit']);
$routes->post('servicios-inmueble/(:num)', 'ServiciosInmuebleController::update/$1', ['filter' => 'auth', 'as' => 'servicios.update']);
$routes->post('servicios-inmueble/(:num)/estado', 'ServiciosInmuebleController::changeState/$1', ['filter' => 'auth', 'as' => 'servicios.state']);
$routes->post('servicios-inmueble/(:num)/baja', 'ServiciosInmuebleController::deactivate/$1', ['filter' => 'auth', 'as' => 'servicios.deactivate']);

$routes->get('facturas', 'FacturasController::index', ['filter' => 'auth', 'as' => 'facturas.index']);
$routes->get('servicios-inmueble/(:num)/facturas/nuevo', 'FacturasController::create/$1', ['filter' => 'auth', 'as' => 'facturas.create']);
$routes->post('servicios-inmueble/(:num)/facturas', 'FacturasController::store/$1', ['filter' => 'auth', 'as' => 'facturas.store']);

$routes->get('catalogo-servicios', 'CatalogoServiciosController::index', ['filter' => 'auth', 'as' => 'catalogo.servicios']);

$routes->group('admin', ['filter' => ['auth', 'role:admin']], static function (RouteCollection $routes): void {
    $routes->get('usuarios', 'Admin\UsuariosController::index', ['as' => 'admin.usuarios.index']);
    $routes->get('usuarios/nuevo', 'Admin\UsuariosController::create', ['as' => 'admin.usuarios.create']);
    $routes->post('usuarios', 'Admin\UsuariosController::store', ['as' => 'admin.usuarios.store']);
    $routes->get('usuarios/(:num)/editar', 'Admin\UsuariosController::edit/$1', ['as' => 'admin.usuarios.edit']);
    $routes->get('usuarios/(:num)', 'Admin\UsuariosController::show/$1', ['as' => 'admin.usuarios.show']);
    $routes->post('usuarios/(:num)', 'Admin\UsuariosController::update/$1', ['as' => 'admin.usuarios.update']);
    $routes->post('usuarios/(:num)/bloquear', 'Admin\UsuariosController::block/$1', ['as' => 'admin.usuarios.block']);
    $routes->post('usuarios/(:num)/baja', 'Admin\UsuariosController::deactivate/$1', ['as' => 'admin.usuarios.deactivate']);
    $routes->post('usuarios/(:num)/reactivar', 'Admin\UsuariosController::activate/$1', ['as' => 'admin.usuarios.activate']);
    $routes->post('usuarios/(:num)/restablecer-contrasenia', 'Admin\UsuariosController::resetPassword/$1', ['as' => 'admin.usuarios.reset-password']);

    $routes->get('tipos-servicio', 'Admin\TiposServicioController::index', ['as' => 'admin.tipos.index']);
    $routes->get('tipos-servicio/nuevo', 'Admin\TiposServicioController::create', ['as' => 'admin.tipos.create']);
    $routes->post('tipos-servicio', 'Admin\TiposServicioController::store', ['as' => 'admin.tipos.store']);
    $routes->get('tipos-servicio/(:num)', 'Admin\TiposServicioController::show/$1', ['as' => 'admin.tipos.show']);
    $routes->get('tipos-servicio/(:num)/editar', 'Admin\TiposServicioController::edit/$1', ['as' => 'admin.tipos.edit']);
    $routes->post('tipos-servicio/(:num)', 'Admin\TiposServicioController::update/$1', ['as' => 'admin.tipos.update']);
    $routes->post('tipos-servicio/(:num)/desactivar', 'Admin\TiposServicioController::deactivate/$1', ['as' => 'admin.tipos.deactivate']);
    $routes->post('tipos-servicio/(:num)/reactivar', 'Admin\TiposServicioController::activate/$1', ['as' => 'admin.tipos.activate']);
    $routes->post('tipos-servicio/(:num)/eliminar', 'Admin\TiposServicioController::destroy/$1', ['as' => 'admin.tipos.destroy']);

    $routes->get('inmuebles', 'Admin\InmueblesController::index', ['as' => 'admin.inmuebles.index']);
});
