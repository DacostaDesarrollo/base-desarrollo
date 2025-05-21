<?php

declare(strict_types=1);

namespace Src\Middlewares\Files;


use Slim\Psr7\UploadedFile;
//Manejo de excepción archivos
use Slim\Psr7\RuntimeException as RuntimeException;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class UploadedFilesMiddleware implements Middleware
{
	private $pathUploads;

	public function __construct(array $pathUploads)
	{
		$this->pathUploads = $pathUploads;
	}

	public function getSettinsUploads(){
		return $this->pathUploads;
	}

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
		
		$valid = $request->getParsedBody();
		$uploadedFiles = $request->getUploadedFiles();
		$files = [];
		$uploadedFile = $uploadedFiles['files'];
		
		if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
			
			$filename = $uploadedFile->getClientFilename();
			$MediaType = $uploadedFile->getClientMediaType();
			$MediaSize = $uploadedFile->getSize();
			
			$fileNameClean = preg_replace("/[^a-z0-9\_\-\.]/i", '',$filename);
			$extension = pathinfo($filename, PATHINFO_EXTENSION);
			
			$basename = bin2hex(random_bytes(3)); // see http://php.net/manual/en/function.random-bytes.php
			//nuevo nombre del archivo sin espacios ni caracteres
			$filename = sprintf('%s', $basename.$fileNameClean);
			// configutación
			$settingsPath = $this->getSettinsUploads();

			$targetDir = $settingsPath['path']  . DIRECTORY_SEPARATOR . (string) $valid['path'];
			//crear las carpetas si no existen
			if (!file_exists($targetDir)) {
				@mkdir($targetDir,0755,true);
			}
			
			//Mover el archivo
			try {
				
				$uploadedFile->moveTo($targetDir. DIRECTORY_SEPARATOR . $filename);
				
				$files = array(
					'nombre_archivo'=> (string) $filename,
					'ruta_archivo'=> (string) $valid['path'].DIRECTORY_SEPARATOR.$filename,
					'tipo_archivo'=> (string) $MediaType,
					'extension_archivo'=> (string) $extension,
					'tamano_archivo'=> (int) $MediaSize,
				);
				
				
			} catch (RuntimeException $e) {
				
			}
		}else{

			switch ($uploadedFile->getError()) {
				case UPLOAD_ERR_OK:
					// No hay error, el archivo se cargó correctamente.
					break;
				case UPLOAD_ERR_INI_SIZE:
					$errorMessage = 'El archivo excede la directiva upload_max_filesize en php.ini';
					break;
				case UPLOAD_ERR_FORM_SIZE:
					$errorMessage = 'El archivo excede el tamaño máximo permitido por el formulario';
					break;
				case UPLOAD_ERR_PARTIAL:
					$errorMessage = 'El archivo se cargó parcialmente';
					break;
				case UPLOAD_ERR_NO_FILE:
					$errorMessage = 'No se cargó ningún archivo';
					break;
				case UPLOAD_ERR_NO_TMP_DIR:
					$errorMessage = 'Falta el directorio temporal';
					break;
				case UPLOAD_ERR_CANT_WRITE:
					$errorMessage = 'Error al escribir el archivo en el disco';
					break;
				case UPLOAD_ERR_EXTENSION:
					$errorMessage = 'Una extensión de PHP detuvo la carga del archivo';
					break;
				default:
					$errorMessage = 'Error desconocido al cargar el archivo';
					break;
			}

            $response = new \Slim\Psr7\Response();
            $response = $response->withStatus(400);
            
            $response->getBody()->write(json_encode(array(
                'statusCode'=>400,
                'description'=>'Error al subir el archivo, puede que el nombre sea muy largo o tenga caracteres especiales!',
                'error'=>$errorMessage
            )));
            
            return $response->withHeader('Content-Type', 'application/json');
		}

		$request = $request->withAttribute('filesUploaded', $files);	

		return $handler->handle($request);
    }
	
	
    
}
