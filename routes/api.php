<?php
use Core\Middleware\ApiTokenMiddleware;
//use Core\Middleware\AuthMiddleware;
use Core\Middleware\AdminMiddleware;
use Src\Models\Token;
use Src\Models\User;

$route->group(function ($route) use ($pdo, $authController, $adminController, $customerController, $appointmentController) {
    $route->prefix('api');


    $route->group(function ($route) use ($authController) {
        $route->prefix('auth');
        $route->post('/register', fn($req, $res) => $authController->register($req, $res));
        $route->post('/login', fn($req, $res) => $authController->login($req, $res));
    });

    $route->middleware(new ApiTokenMiddleware(new Token($pdo)));

    $route->group(function ($route) use ($authController) {
        $route->prefix('auth');

        $route->post('/logout', fn($req, $res) => $authController->logout($req, $res));

        $route->get('/profile', fn($req, $res) => $authController->profile($req, $res));

    });

    $route->group(function ($route) use ($pdo, $adminController, $customerController, $appointmentController) {
        $route->prefix('admin');
        $route->middleware(new AdminMiddleware(new User($pdo)));

        $route->get('/listUser', fn($req, $res) => $adminController->get($req, $res));
        $route->put('/active/{id}', fn($req, $res) => $adminController->activeUser($req, $res));

        $route->group(function ($route) use ($pdo, $customerController) {
            $route->prefix('customer');
            $route->post('/create', fn($req, $res) => $customerController->create($req, $res));
            $route->get('/get/{page}', fn($req, $res) => $customerController->all($req, $res));
            $route->put('/update/{id}', fn($req, $res) => $customerController->update($req, $res));
            $route->get('/show/{id}', fn($req, $res) => $customerController->show($req, $res));
            $route->delete('/delete/{id}', fn($req, $res) => $customerController->delete($req, $res));
        });

        $route->group(function ($route) use ($appointmentController) {
            $route->prefix('appointment');
            $route->post('/create', fn($req, $res) => $appointmentController->create($req, $res));
            $route->put('/update/{id}', fn($req, $res) => $appointmentController->update($req, $res));
            $route->get('/all/{page}', fn($req, $res) => $appointmentController->all($req, $res));
            $route->get('/customers/last/{customerId}', fn($req, $res) => $appointmentController->getLastByCustomer($req, $res));
            $route->get('/customers/all/{customerId}', fn($req, $res) => $appointmentController->getAllByCustomer($req, $res));

        });
    });
});
