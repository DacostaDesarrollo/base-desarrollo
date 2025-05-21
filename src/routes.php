<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Slim\Exception\HttpNotFoundException;
use Src\Middlewares\Files\UploadedFilesMiddleware;
use Src\Middlewares\Files\FilesValidMiddleware;

return function (App $app) {
    $container = $app->getContainer();
    
    $app->options('/{routes:.*}', function (Request $request, Response $response, $handler) {
        return $handler->handle($request);
    });

    $app->get('/', function(Request $request, Response $response, array $args = []) use ($container) {
        return $container->get('homeController')->statusApi($request, $response, $args);
    });

    $app->group('/users', function (Group $group) use ($container) {
        $group->get('', function(Request $request, Response $response) use ($container) {
            return $container->get('userController')->listUsers($request, $response);
        });
        $group->get('/{id}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('userController')->getUserId($request, $response, $args);
        });
        $group->put('/{idUser}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('userController')->updateUser($request, $response, $args);
        });
        $group->delete('/{idUser}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('userController')->deleteUser($request, $response, $args);
        });
    })->add(function($request, $handler) use ($container) {
        $roleMiddleware = $container->get('roleMiddleware');
        return $roleMiddleware->setRoles(['admin', 'jurado'])->process($request, $handler);
    })->add($container->get('authMiddleware'));

    $app->group('/auth', function (Group $group) use ($container) {
        $group->post('/login', function(Request $request, Response $response, $arg) use ($container) {
            return $container->get('authService')->login($request, $response, $arg);
        });
        $group->post('/register', function(Request $request, Response $response, $arg) use ($container) {
            return $container->get('authService')->register($request, $response, $arg);
        });
        $group->post('/forgot-password', function(Request $request, Response $response, $arg) use ($container) {
            return $container->get('authService')->forgotPassword($request, $response, $arg);
        });
        $group->post('/reset-password', function(Request $request, Response $response, $arg) use ($container) {
            return $container->get('authService')->resetPassword($request, $response, $arg);
        });
    });

    $app->group('/payments', function (Group $group) use ($container) {
        $group->post('/method/payu/create', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('paymentsController')->createPayment($request, $response, $args);
        });
        $group->post('/method/payu/responseUrl', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('paymentsController')->responseUrl($request, $response, $args);
        });
        $group->post('/method/payu/confirmationUrl', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('paymentsController')->confirmationUrl($request, $response, $args);
        });
        $group->get('/method/payu/status/{id}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('paymentsController')->getPaymentStatus($request, $response, $args);
        });
        $group->get('/method/payu/list', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('paymentsController')->listPayments($request, $response, $args);
        });
        $group->get('/method/payu/list/{userId}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('paymentsController')->listUserPayments($request, $response, $args);
        });
    })->add($container->get('authMiddleware'));

    $app->group('/suscriptions', function (Group $group) use ($container) {
        $group->get('', function(Request $request, Response $response) use ($container) {
            return $container->get('suscriptionController')->listSuscriptions($request, $response);
        });
        $group->get('/{id}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('suscriptionController')->getSuscriptionId($request, $response, $args);
        });
        $group->post('', function(Request $request, Response $response) use ($container) {
            return $container->get('suscriptionController')->createSuscription($request, $response);
        });
        $group->put('/{id}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('suscriptionController')->updateSuscription($request, $response, $args);
        });
        $group->delete('/{id}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('suscriptionController')->deleteSuscription($request, $response, $args);
        });
    })->add($container->get('authMiddleware'));

     /**
     * Rotas de los países
    */
    $app->group('/paises', function (Group $group) use ($container) {
        $group->get('', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('countryController')->getCountrys($request, $response, $args);
        });
    });
    //Ruta para subir archivos
    $app->group('/files', function (Group $group) use ($container) {
        $group->post('', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('filesController')->saveFile($request, $response, $args);
        })->add(new FilesValidMiddleware())->add(new UploadedFilesMiddleware($container->get('settings')['uploads']));
        
        $group->get('/group', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('filesController')->getFilesGroup($request, $response, $args);
        });
        
        $group->get('/assets/{path}/{filename}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('filesController')->getStreamFile($request, $response, $args);
        });
        
        $group->delete('/{id}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get('filesController')->deleteFile($request, $response, $args);
        });
    });

    // Catch-all route
    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
        throw new HttpNotFoundException($request);
    });
};
