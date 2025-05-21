<?php
namespace Src\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

use Illuminate\Database\Capsule\Manager as DB;
use Src\Models\Usuario as Usuario;

class HomeController {

    public function __construct(){}
    
    public function home(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
      $users = Usuario::all();
      //$session = $request->getAttribute('session');
      $response->getBody()->write(json_encode($users));

      return $response->withHeader('Content-Type', 'application/json');
    }
    public function statusApi(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
		$status = [
			'Version_api' => '1.0.0',
            'php_version' => phpversion(),
            'db_connection' => false,
            'error' => null,
        ];

        try {
            // Verifica la conexión a la base de datos
            DB::connection()->getPdo();
            $status['db_connection'] = true;

        } catch (\Exception $e) {
            // Si ocurre un error, lo registra en el estado
            $status['error'] = $e->getMessage();
        }

        $response->getBody()->write(json_encode($status));

        return $response->withHeader('Content-Type', 'application/json');
    }
}