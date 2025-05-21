<?php

declare(strict_types=1);

// Should be set to 0 in production
error_reporting(E_ALL);

use DI\ContainerBuilder;
use Monolog\Logger;
use Dotenv\Dotenv;
// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
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
                    'driver' => 'mysql',
                    'host' => $_ENV['DB_HOST'],
                    'database' => $_ENV['DB_NAME'],
                    'username' => $_ENV['DB_USER'],
                    'password' => $_ENV['DB_PASS'],
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
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
    ]);
};

