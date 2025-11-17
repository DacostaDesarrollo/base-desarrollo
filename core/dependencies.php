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
use Src\Services\Auth\AuthService;
use Src\Middlewares\Auth\AuthMiddleware;
use Src\Middlewares\Auth\RoleMiddleware;
use Src\Middlewares\Performance\ProfilingMiddleware;
use Src\Controllers\BaseController;
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

    // Email Service
    'emailService' => function (ContainerInterface $c) {
        return new EmailService(
            $c->get('settings')['smtp'],
            $c->get('twig')
        );
    },

    // Auth Service
    'authService' => function (ContainerInterface $c) {
        $settings = $c->get('settings');
        if (!isset($settings['secretKey']) || empty($settings['secretKey'])) {
            throw new \RuntimeException('La clave secreta JWT no está configurada');
        }
        return new AuthService(
            $settings['secretKey'],
            $c->get('emailService')
        );
    },

    // Auth Middleware
    'authMiddleware' => function (ContainerInterface $c) {
        return new AuthMiddleware($c->get('authService'));
    },

    // Role Middleware
    'roleMiddleware' => function (ContainerInterface $container) {
        return new RoleMiddleware($container->get(ResponseFactoryInterface::class));
    },

    // Base Controller
    BaseController::class => function (ContainerInterface $c) {
        return new BaseController($c);
    },

    // Controllers (lazy loaded)
    'userController' => function (ContainerInterface $c) {
        return new UserController($c);
    },

    'homeController' => function (ContainerInterface $c) {
        return new HomeController($c);
    },

    'categoryController' => function (ContainerInterface $c) {
        return new CategoryController($c);
    },

    'countryController' => function (ContainerInterface $c) {
        return new CountryController($c);
    },

    'filesController' => function (ContainerInterface $c) {
        return new FilesController($c);
    },

    'suscriptionController' => function (ContainerInterface $c) {
        return new SuscriptionController($c);
    },

    'invoiceController' => function (ContainerInterface $c) {
        return new InvoiceController($c);
    },

    'paymentsController' => function (ContainerInterface $c) {
        return new PaymentsController($c);
    },

    'proyectController' => function (ContainerInterface $c) {
        return new ProyectController($c);
    },

    'adwardController' => function (ContainerInterface $c) {
        return new AdwardController($c);
    },

    'evaluationCriteriaController' => function (ContainerInterface $c) {
        return new EvaluationCriteriaController($c);
    },

    // Profiling Middleware
    'profilingMiddleware' => function (ContainerInterface $container) {
        return new ProfilingMiddleware();
    },
];