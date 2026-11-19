<?php
declare(strict_types=1);

/** @var Router $router */

// Público
$router->get('', 'HomeController@index', ['guest']);
$router->get('login', 'AuthController@showLogin', ['guest']);
$router->post('login', 'AuthController@login', ['guest']);
$router->get('registro', 'AuthController@showRegister', ['guest']);
$router->post('registro', 'AuthController@register', ['guest']);
$router->get('api/estaciones', 'MapController@apiEstaciones');
$router->get('api/electrolineras', 'MapController@apiElectrolineras');

// Sesión
$router->post('logout', 'AuthController@logout', ['auth']);
$router->get('dashboard', 'DashboardController@index', ['auth']);

// Perfil
$router->get('perfil', 'ProfileController@show', ['auth']);
$router->post('perfil/actualizar', 'ProfileController@update', ['auth']);
$router->post('perfil/password', 'ProfileController@changePassword', ['auth']);

// Mapa
$router->get('mapa', 'MapController@index', ['auth']);

// Bicicletas
$router->get('bicicletas', 'BikeController@index', ['auth']);
$router->get('bicicletas/crear', 'BikeController@create', ['staff']);
$router->post('bicicletas', 'BikeController@store', ['staff']);
$router->get('bicicletas/editar/{id}', 'BikeController@edit', ['staff']);
$router->post('bicicletas/{id}', 'BikeController@update', ['staff']);
$router->post('bicicletas/{id}/eliminar', 'BikeController@destroy', ['admin']);

// Estaciones
$router->get('estaciones', 'StationController@index', ['auth']);
$router->get('estaciones/crear', 'StationController@create', ['admin']);
$router->post('estaciones', 'StationController@store', ['admin']);
$router->get('estaciones/editar/{id}', 'StationController@edit', ['admin']);
$router->post('estaciones/{id}', 'StationController@update', ['admin']);
$router->post('estaciones/{id}/eliminar', 'StationController@destroy', ['admin']);

// Usuarios
$router->get('usuarios', 'UserController@index', ['admin']);
$router->get('usuarios/crear', 'UserController@create', ['admin']);
$router->post('usuarios', 'UserController@store', ['admin']);
$router->get('usuarios/editar/{id}', 'UserController@edit', ['admin']);
$router->post('usuarios/{id}', 'UserController@update', ['admin']);
$router->post('usuarios/{id}/eliminar', 'UserController@destroy', ['admin']);

// Alquileres
$router->get('alquileres', 'RentalController@index', ['auth']);
$router->get('alquileres/iniciar', 'RentalController@create', ['auth']);
$router->post('alquileres/iniciar', 'RentalController@store', ['auth']);
$router->get('alquileres/finalizar/{id}', 'RentalController@endForm', ['auth']);
$router->post('alquileres/finalizar/{id}', 'RentalController@end', ['auth']);
$router->get('alquileres/ver/{id}', 'RentalController@show', ['auth']);
$router->post('alquileres/{id}/cancelar', 'RentalController@cancel', ['staff']);

// Mantenimientos
$router->get('mantenimientos', 'MaintenanceController@index', ['staff']);
$router->get('mantenimientos/crear', 'MaintenanceController@create', ['staff']);
$router->post('mantenimientos', 'MaintenanceController@store', ['staff']);
$router->get('mantenimientos/editar/{id}', 'MaintenanceController@edit', ['staff']);
$router->post('mantenimientos/{id}', 'MaintenanceController@update', ['staff']);
$router->post('mantenimientos/{id}/eliminar', 'MaintenanceController@destroy', ['admin']);

// Energía
$router->get('energia', 'EnergyController@index', ['staff']);
$router->get('energia/crear', 'EnergyController@create', ['staff']);
$router->post('energia', 'EnergyController@store', ['staff']);
$router->get('energia/editar/{id}', 'EnergyController@edit', ['staff']);
$router->post('energia/{id}', 'EnergyController@update', ['staff']);
$router->post('energia/{id}/eliminar', 'EnergyController@destroy', ['admin']);

// Pagos
$router->get('pagos', 'PaymentController@index', ['auth']);
$router->post('pagos/metodos', 'PaymentController@storeMethod', ['auth']);
$router->post('pagos/metodos/{id}/eliminar', 'PaymentController@destroyMethod', ['auth']);
$router->post('pagos/reembolsar/{id}', 'PaymentController@refund', ['admin']);

// Reportes
$router->get('reportes', 'ReportController@index', ['staff']);
