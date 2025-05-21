<?php
namespace Src\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Illuminate\Database\Capsule\Manager as DB;
use Src\Models\Pais;

class CountryController {

    public function __construct(){}
    
    public function getCountrys(Request $request,Response $response, array $args): Response
    {
        $params = $request->getQueryParams(); // Obtén el número de página de los parámetros de la URL
        
        //Paginación
        $perPage = isset($params['perPage'])?$params['perPage']:15; // Número de elementos por página
        $page = isset($params['page'])?$params['page']:1; // Página actual

        $offset = ($page - 1) * $perPage; // Calcula el desplazamiento
        
        $paises = DB::table('paises')
        //->skip(2)
        //->take(4);
        ->offset($offset)
        ->limit($perPage)->get();


        if ($paises){

            $response->withStatus(200)
                ->getBody()
                ->write(json_encode(array(
                    'statusCode' => $response->getStatusCode(),
                    'paises'=> $paises->toArray()
            )), JSON_UNESCAPED_SLASHES);

            
        }else{

            $response->withStatus(404)
            ->getBody()
                ->write(json_encode(array(
                    'statusCode' => $response->getStatusCode(),
                    'paises'=> $paises->toArray()
                )), JSON_UNESCAPED_SLASHES);
        }
                
        return $response->withHeader('Content-Type', 'application/json');
       
    }
}