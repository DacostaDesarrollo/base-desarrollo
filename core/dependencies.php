<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Middleware\ErrorMiddleware;
use Src\Services\EmailService;
use Src\Services\LoggerService;
use Src\Services\Auth\AuthService;
use Monolog\Handler\RotatingFileHandler;
use Src\Repositories\Auth\AuthRepositoryInterface;
use Src\Repositories\Auth\EloquentAuthRepository;
use Src\Middlewares\Auth\AuthMiddleware;
use Src\Middlewares\Auth\RoleMiddleware;
use Src\Middlewares\Performance\ProfilingMiddleware;
use Src\Controllers\BaseController;
use Src\Controllers\Auth\AuthController;
use Src\Controllers\UserController;
use Src\Controllers\HomeController;
use Src\Controllers\CategoryController;
use Src\Controllers\CountryController;
use Src\Controllers\FilesController;
use Src\Controllers\SuscriptionController;
use Src\Controllers\InvoiceController;
use Src\Controllers\PaymentsController;
use Src\Controllers\ProyectController;
use Src\Controllers\AdwardController;
use Src\Controllers\EvaluationCriteriaController;

return [
    // Settings
    'settings' => function () {
        return [
            'displayErrorDetails' => $_ENV['APP_DEBUG'] === 'true',
            'logError' => true,
            'logErrorDetails' => true,
            'logger' => [
                'name' => $_ENV['LOGGER_NAME'],
                'path' => $_ENV['LOGGER_PATH'],
                'level' => $_ENV['LOGGER_LEVEL'],
            ],
            'base_path' => $_ENV['APP_BASE_PATH'],
            'db' => [
                'driver' => $_ENV['DB_DRIVER'] ?? 'pgsql',
                'host' => $_ENV['DB_HOST'],
                'database' => $_ENV['DB_NAME'],
                'username' => $_ENV['DB_USER'],
                'password' => $_ENV['DB_PASS'],
                'charset' => $_ENV['DB_CHARSET'] ?? 'utf8',
                'collation' => $_ENV['DB_COLLATION'] ?? '',
                'port' => $_ENV['DB_PORT'],
                'prefix' => '',
            ],
            'smtp' => [
                'host' => $_ENV['SMTP_HOST'],
                'username' => $_ENV['SMTP_USER'],
                'password' => $_ENV['SMTP_PASS'],
                'port' => $_ENV['SMTP_PORT'],
                'SMTPSecure' => $_ENV['SMTP_ENCRYPTION'],
                'from' => [
                    'address' => $_ENV['SMTP_FROM_ADDRESS'],
                    'name' => $_ENV['SMTP_FROM_NAME'],
                ],
            ],
            'payments' => [
                'payu' => [
                    'test' => $_ENV['PAYU_TEST'],
                    'merchantId' => $_ENV['PAYU_MERCHANT_ID'],
                    'accountId' => $_ENV['PAYU_ACCOUNT_ID'],
                    'action' => $_ENV['PAYU_ACTION'],
                    'apikey' => $_ENV['PAYU_API_KEY'],
                    'responseUrl' => $_ENV['PAYU_RESPONSE_URL'],
                    'confirmationUrl' => $_ENV['PAYU_CONFIRMATION_URL'],
                ],
            ],
            'twig' => [
                'path' => __DIR__ . '/../templates',
                'cache_enabled' => __DIR__ . '/../var/cache/twig',
            ],
            'uploads' => [
                'path' => __DIR__ . '/../uploads',
            ],
            'secretKey' => $_ENV['JWT_SECRET'],
        ];
    },

    // Response Factory
    ResponseFactoryInterface::class => function (ContainerInterface $c) {
        return new ResponseFactory();
    },

    // Twig
    'twig' => function (ContainerInterface $c) {
        $twig = Twig::create(__DIR__ . '/../templates', [
            'cache' => __DIR__ . '/../var/cache/twig',
            'auto_reload' => true,
            'debug' => true,
        ]);
        return $twig;
    },

    // Logger (Monolog)
    LoggerInterface::class => function (ContainerInterface $c) {
        $settings = $c->get('settings')['logger'];
        $logger = new Logger($settings['name']);

        // Handler para errores - archivos rotativos diarios
        $errorHandler = new RotatingFileHandler(
            __DIR__ . '/../logs/errors.log',
            30, // Mantener 30 días de logs
            Logger::ERROR
        );
        $logger->pushHandler($errorHandler);

        // Handler para warnings
        $warningHandler = new RotatingFileHandler(
            __DIR__ . '/../logs/warnings.log',
            30,
            Logger::WARNING
        );
        $logger->pushHandler($warningHandler);

        // Handler para info (eventos generales)
        $infoHandler = new RotatingFileHandler(
            __DIR__ . '/../logs/info.log',
            7, // Info solo 7 días
            Logger::INFO
        );
        $logger->pushHandler($infoHandler);

        $logger->pushProcessor(new UidProcessor());
        return $logger;
    },

    // Logger Service (wrapper con contexto)
    LoggerService::class => fn(ContainerInterface $c) => new LoggerService(
        $c->get(LoggerInterface::class),
        $c->get('settings')
    ),

    // Email Service
    EmailService::class => fn(ContainerInterface $c) => new EmailService(
        $c->get('settings')['smtp'],
        $c->get('twig')
    ),

    // Auth Repository
    AuthRepositoryInterface::class => fn() => new EloquentAuthRepository(),

    // Auth Service
    AuthService::class => fn(ContainerInterface $c) => new AuthService(
        $c->get(AuthRepositoryInterface::class),
        $c->get('settings')['secretKey'],
        $c->get(EmailService::class)
    ),

    // Auth Controller
    AuthController::class => fn(ContainerInterface $c) => new AuthController($c),

    // Middlewares
    AuthMiddleware::class => fn(ContainerInterface $c) => new AuthMiddleware($c->get(AuthService::class)),
    
    RoleMiddleware::class => fn(ContainerInterface $c) => new RoleMiddleware($c->get(ResponseFactoryInterface::class)),
    
    ProfilingMiddleware::class => fn() => new ProfilingMiddleware(),

    // Controllers
    BaseController::class => fn(ContainerInterface $c) => new BaseController($c),
    
    UserController::class => fn(ContainerInterface $c) => new UserController($c),
    
    HomeController::class => fn(ContainerInterface $c) => new HomeController($c),
    
    CategoryController::class => fn(ContainerInterface $c) => new CategoryController($c),
    
    CountryController::class => fn(ContainerInterface $c) => new CountryController($c),
    
    FilesController::class => fn(ContainerInterface $c) => new FilesController($c),
    
    SuscriptionController::class => fn(ContainerInterface $c) => new SuscriptionController($c),
    
    InvoiceController::class => fn(ContainerInterface $c) => new InvoiceController($c),
    
    PaymentsController::class => fn(ContainerInterface $c) => new PaymentsController($c),
    
    ProyectController::class => fn(ContainerInterface $c) => new ProyectController($c),
    
    AdwardController::class => fn(ContainerInterface $c) => new AdwardController($c),
    
    EvaluationCriteriaController::class => fn(ContainerInterface $c) => new EvaluationCriteriaController($c),
];