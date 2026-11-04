<?php

require_once __DIR__ . '/autoload.php';


use Core\Env;
use Core\Http\Request;
use Core\Http\Response;
use Core\Middleware\CorsMiddleware;
use Core\Route;
use Core\Database\Connection;

use Src\Auth\AuthController;
use Src\Auth\AuthService;
use Src\Controllers\AdminController;
use Src\Controllers\CustomerController;
use Src\Controllers\EjemploController;
use Src\Models\Customer;
use Src\Models\Token;
use Src\Models\User;
use Src\Repository\CustomerRepository;
use Src\Repository\UserRepository;
use Src\Service\AdminService;
use Src\Service\CustomerService;

Env::load(__DIR__ . '/../.env');

$req = new Request();
$res = new Response();
$route = new Route();

$pdo = Connection::connect();

$cors = new CorsMiddleware(
    allowedOrigins: [
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'https://app.midominio.com',
    ],
    allowedMethods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With'],
    allowCredentials: true,
    maxAge: 86400,
);


$authController = new AuthController(new AuthService(new UserRepository(new User($pdo))), new Token($pdo));
$ejemploController = new EjemploController();
$adminController = new AdminController(new AdminService(new UserRepository(new User($pdo))));
$customerController = new CustomerController(new CustomerService(new CustomerRepository(new Customer($pdo))));

$route->group(function ($route) use ($cors, $pdo, $authController, $adminController, $customerController) {
    $route->middleware($cors);
    require __DIR__ . '/../routes/api.php';
});
/* 
echo "<pre>";
print_r($route->getRoutes());
echo "</pre>";
exit; */

$route->dispatch($req, $res, 'Pagina no encontrada.', $cors);