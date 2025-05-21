<?php

declare(strict_types=1);
use DI\ContainerBuilder;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Container\ContainerInterface;

return function (ContainerInterface $container) {
    $capsule = new Capsule;
    
    $settings = $container->get('settings');
    $dbSettings = $settings['db'];

    $capsule->addConnection([
        'driver'    => $dbSettings['driver'],
        'host'      => $dbSettings['host'],
        'database'  => $dbSettings['database'],
        'username'  => $dbSettings['username'],
        'password'  => $dbSettings['password'],
        'charset'   => $dbSettings['charset'],
        'collation' => $dbSettings['collation'],
        'prefix'    => $dbSettings['prefix'],
    ]);

    $capsule->setAsGlobal();
    $capsule->bootEloquent();

    return $capsule;
};
