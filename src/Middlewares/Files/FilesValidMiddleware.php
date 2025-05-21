<?php

declare(strict_types=1);

namespace Src\Middlewares\Files;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;


class FilesValidMiddleware implements Middleware
{
	private $extentions;
	private $requiredFile;

	public function __construct(){
	}

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
		$valid 			= $request->getParsedBody();
		$uploadedFiles 	= $request->getUploadedFiles();
 
		$required		= (bool) $valid['required'];
		//$multiple		= (bool) $valid['multiple'];
		$uploadedFile 	= $uploadedFiles['files'];
        
        $allowedExtensions = (array) explode(',',(string) $valid['extension']);

		//validar si el archivo es obligatorio
        if ((bool) $required && !$uploadedFile->getError() === UPLOAD_ERR_OK) {
            
            $response = new \Slim\Psr7\Response();
            $response = $response->withStatus(400);
            
            $response->getBody()->write(json_encode(array(
                'statusCode'=>400,
                'description'=>'El archivo es requerido.'
            )));
            
            return $response->withHeader('Content-Type', 'application/json');
        }
        
        // Validamos la extensión del archivo
            
        $filename = $uploadedFile->getClientFilename();
        
        if ($filename == "") {
            # code...
            $response = new \Slim\Psr7\Response();
            $response = $response->withStatus(400);
            
            $response->getBody()->write(json_encode(array(
                'statusCode'=>400,
                'description'=>'El archivo es requerido.'
            )));
            
            return $response->withHeader('Content-Type', 'application/json');
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $extentionsGroup = [
            'image'=>[
                'jpg',
                'png',
                'jpeg',
            ],
            'pdf'=>[
                'pdf'
            ],
            'xls'=>[
                'pdf'
            ],
            'doc'=>[
                'docx',
                'doc'
            ]
            
        ];

       /*  foreach ($extentionsGroup as $key => $group) {
            if (condition) {
                # code...
            }
        } */


        /* if (!in_array(strtolower($extension),$allowedExtensions) && $required) {
            
            $response = new \Slim\Psr7\Response();
            $response = $response->withStatus(400);
            
            $response->getBody()->write(json_encode(array(
                'statusCode'=>400,
                'description'=>'La extensión del archivo no es válida.'
            
            )));

            return $response->withHeader('Content-Type', 'application/json');
        } */
     

		//Si pasa todas las validaciones continua la petición
		return $handler->handle($request);
		
    }
	
	
    
}
