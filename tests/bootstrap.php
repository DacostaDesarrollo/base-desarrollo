<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar variables de entorno para testing
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configurar timezone
date_default_timezone_set('America/Bogota');
