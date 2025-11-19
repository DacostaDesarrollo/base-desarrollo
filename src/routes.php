<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Slim\Exception\HttpNotFoundException;
use Src\Middlewares\Files\UploadedFilesMiddleware;
use Src\Middlewares\Files\FilesValidMiddleware;
use Src\Controllers\AuthController;
use Src\Controllers\UserController;
use Src\Controllers\HomeController;
use Src\Controllers\CountryController;
use Src\Controllers\FilesController;
use Src\Controllers\SuscriptionController;
use Src\Controllers\PaymentsController;

return function (App $app) {
    $container = $app->getContainer();
    
    $app->options('/{routes:.*}', function (Request $request, Response $response, $handler) {
        return $handler->handle($request);
    });

    $app->get('/', function(Request $request, Response $response, array $args = []) use ($container) {
        return $container->get(HomeController::class)->statusApi($request, $response, $args);
    });

    $app->group('/users', function (Group $group) use ($container) {
        $group->get('', function(Request $request, Response $response) use ($container) {
            return $container->get(UserController::class)->listUsers($request, $response);
        });
        $group->get('/{id}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(UserController::class)->getUserId($request, $response, $args);
        });
        $group->put('/{idUser}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(UserController::class)->updateUser($request, $response, $args);
        });
        $group->delete('/{idUser}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(UserController::class)->deleteUser($request, $response, $args);
        });
    })->add(function($request, $handler) use ($container) {
        $roleMiddleware = $container->get(\Src\Middlewares\Auth\RoleMiddleware::class);
        return $roleMiddleware->setRoles(['admin', 'jurado'])->process($request, $handler);
    })->add($container->get(\Src\Middlewares\Auth\AuthMiddleware::class));

    $app->group('/auth', function (Group $group) use ($container) {
        
        $group->post('/login', function(Request $request, Response $response, $arg) use ($container) {
            return $container->get(AuthController::class)->login($request, $response, $arg);
        });
        $group->post('/register', function(Request $request, Response $response, $arg) use ($container) {
            return $container->get(AuthController::class)->register($request, $response, $arg);
        });
        $group->post('/forgot-password', function(Request $request, Response $response, $arg) use ($container) {
            return $container->get(AuthController::class)->forgotPassword($request, $response, $arg);
        });
        $group->post('/reset-password', function(Request $request, Response $response, $arg) use ($container) {
            return $container->get(AuthController::class)->newPassword($request, $response, $arg);
        });
    });

    $app->group('/payments', function (Group $group) use ($container) {
        $group->post('/method/payu/create', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(PaymentsController::class)->createPayment($request, $response, $args);
        });
        $group->post('/method/payu/responseUrl', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(PaymentsController::class)->responseUrl($request, $response, $args);
        });
        $group->post('/method/payu/confirmationUrl', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(PaymentsController::class)->confirmationUrl($request, $response, $args);
        });
        $group->get('/method/payu/status/{id}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(PaymentsController::class)->getPaymentStatus($request, $response, $args);
        });
        $group->get('/method/payu/list', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(PaymentsController::class)->listPayments($request, $response, $args);
        });
        $group->get('/method/payu/list/{userId}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(PaymentsController::class)->listUserPayments($request, $response, $args);
        });
    })->add($container->get(\Src\Middlewares\Auth\AuthMiddleware::class));

    $app->group('/suscriptions', function (Group $group) use ($container) {
        $group->get('', function(Request $request, Response $response) use ($container) {
            return $container->get(SuscriptionController::class)->listSuscriptions($request, $response);
        });
        $group->get('/{id}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(SuscriptionController::class)->getSuscriptionId($request, $response, $args);
        });
        $group->post('', function(Request $request, Response $response) use ($container) {
            return $container->get(SuscriptionController::class)->createSuscription($request, $response);
        });
        $group->put('/{id}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(SuscriptionController::class)->updateSuscription($request, $response, $args);
        });
        $group->delete('/{id}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(SuscriptionController::class)->deleteSuscription($request, $response, $args);
        });
    })->add($container->get(\Src\Middlewares\Auth\AuthMiddleware::class));

     /**
     * Rotas de los países
    */
    $app->group('/paises', function (Group $group) use ($container) {
        $group->get('', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(CountryController::class)->getCountrys($request, $response, $args);
        });
    });
    //Ruta para subir archivos
    $app->group('/files', function (Group $group) use ($container) {
        $group->post('', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(FilesController::class)->saveFile($request, $response, $args);
        })->add(new FilesValidMiddleware())->add(new UploadedFilesMiddleware($container->get('settings')['uploads']));
        
        $group->get('/group', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(FilesController::class)->getFilesGroup($request, $response, $args);
        });
        
        $group->get('/assets/{path}/{filename}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(FilesController::class)->getStreamFile($request, $response, $args);
        });
        
        $group->delete('/{id}', function(Request $request, Response $response, $args) use ($container) {
            return $container->get(FilesController::class)->deleteFile($request, $response, $args);
        });
    });

    // Catch-all route
    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
        throw new HttpNotFoundException($request);
    });
};
