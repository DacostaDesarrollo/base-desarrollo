<?php
namespace Src\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

use Illuminate\Database\Capsule\Manager as DB;
use Src\Models\Usuario as Usuario;

class HomeController extends BaseController
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }
    
    public function home(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $users = Usuario::all();
            return $this->respondWithData($response, $users);
        } catch (\Exception $e) {
            return $this->respondWithError($response, $e->getMessage(), 500);
        }
    }

    public function statusApi(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        $status = [
            'version_api' => '1.0.0',
            'php_version' => phpversion(),
            'db_connection' => false,
            'error' => null,
        ];

        try {
            // Verifica la conexión a la base de datos
            DB::connection()->getPdo();
            $status['db_connection'] = true;
            return $this->respondWithData($response, $status);
        } catch (\Exception $e) {
            $status['error'] = $e->getMessage();
            return $this->respondWithError($response, $e->getMessage(), 500);
        }
    }
}