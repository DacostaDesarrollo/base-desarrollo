<?php

declare(strict_types=1);

use Src\handlers\HttpErrorHandler;
use Src\handlers\ShutdownHandler;
use DI\ContainerBuilder;
use Slim\Factory\AppFactory;
//use Src\handlers\ResponseEmitter;
use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Middleware\ErrorMiddleware;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Middleware\RoutingMiddleware;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Middleware\CorsMiddleware;
use Src\Middlewares\Performance\ProfilingMiddleware;
use Src\Core\Environment;
use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

// Set the default timezone.
date_default_timezone_set('America/Bogota');


require_once __DIR__ . '/../vendor/autoload.php';

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Obtener el entorno
$env = Environment::getInstance();

// Crear el contenedor PHP-DI
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../core/dependencies.php');
$container = $containerBuilder->build();

// Inicializar Eloquent
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection($container->get('settings')['db']);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Crear la aplicación Slim
$app = AppFactory::createFromContainer($container);

// Configurar el base path PRIMERO
/* $basePath = $env->getBasePath();
if (!empty($basePath)) {
    $app->setBasePath($basePath);
} */

// Agregar middleware de profiling solo en desarrollo
if (!$env->isProduction()) {
    $app->add($container->get('profilingMiddleware'));
}

// Crear el manejador de errores personalizado
$callableResolver = $app->getCallableResolver();
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory);

// Agregar middleware de manejo de errores
$errorMiddleware = new ErrorMiddleware(
    $callableResolver,
    $responseFactory,
    $env->shouldShowErrors(),
    $env->shouldLogErrors(),
    $env->shouldLogDetails()
);
$errorMiddleware->setDefaultErrorHandler($errorHandler);
$app->add($errorMiddleware);

// Crear el manejador de cierre
$shutdownHandler = new ShutdownHandler(
    ServerRequestCreatorFactory::create()->createServerRequestFromGlobals(),
    $errorHandler,
    $env->shouldShowErrors()
);
register_shutdown_function($shutdownHandler);

// Agregar middleware de parsing del body
$app->addBodyParsingMiddleware();

// Cargar rutas ANTES del RoutingMiddleware
$routes = require __DIR__ . '/../src/routes.php';
$routes($app);

// Agregar middleware de routing
$app->addRoutingMiddleware();

// Ejecutar la aplicación
$app->run();