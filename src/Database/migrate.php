<?php

require __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Configuración de la base de datos
$capsule = new Capsule;

// Determinar el driver de la base de datos
$driver = $_ENV['DB_DRIVER'] ?? 'mysql';

// Configuración base de la conexión
$config = [
    'driver'    => $driver,
    'host'      => $_ENV['DB_HOST'] ?? 'localhost',
    'database'  => $_ENV['DB_NAME'] ?? 'app_db',
    'username'  => $_ENV['DB_USER'] ?? 'root',
    'password'  => $_ENV['DB_PASS'] ?? '',
    'prefix'    => '',
];

// Configuración específica según el driver
if ($driver === 'pgsql') {
    $config['charset'] = 'utf8';
    $config['collation'] = 'utf8_unicode_ci';
    $config['schema'] = 'public';
} else {
    $config['charset'] = 'utf8mb4';
    $config['collation'] = 'utf8mb4_unicode_ci';
}

// Agregar la conexión
$capsule->addConnection($config);

// Hacer la conexión global
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Verificar si es una operación de rollback
$isRollback = in_array('--rollback', $argv);

if ($isRollback) {
    // Obtener la última migración ejecutada
    $migrations = glob(__DIR__ . '/Migrations/*.php');
    $lastMigration = end($migrations);
    
    if ($lastMigration) {
        echo "Revirtiendo migración: " . basename($lastMigration) . "\n";
        
        // Crear una nueva instancia del schema para el rollback
        $schema = Capsule::schema();
        
        // Obtener el nombre de la tabla del archivo de migración
        $tableName = basename($lastMigration, '.php');
        $tableName = str_replace('create_', '', $tableName);
        $tableName = str_replace('_table', '', $tableName);
        
        // Eliminar la tabla
        if ($schema->hasTable($tableName)) {
            $schema->drop($tableName);
            echo "Tabla $tableName eliminada.\n";
        }
    }
} else {
    // Ejecutar migraciones
    $migrations = glob(__DIR__ . '/Migrations/*.php');
    sort($migrations); // Asegurar orden alfabético

    foreach ($migrations as $migration) {
        echo "Ejecutando migración: " . basename($migration) . "\n";
        
        try {
            require $migration;
            echo "Migración completada: " . basename($migration) . "\n";
        } catch (\Exception $e) {
            echo "Error en migración " . basename($migration) . ": " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    echo "Todas las migraciones han sido ejecutadas.\n";
} 