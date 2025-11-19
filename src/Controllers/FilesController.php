<?php
namespace Src\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Illuminate\Database\Capsule\Manager as DB;
use Src\Models\Files as Files;
use Exception;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Respect\Validation\Validator as v;
use Src\Validation\Rules\UniqueField; // Asegúrate de importar la clase
use Respect\Validation\Exceptions\ValidationException as ValidationException;
use Respect\Validation\Exceptions\NestedValidationException as NestedValidationException;
use Respect\Validation\Factory;

use Src\Models\Usuario as Usuario;
use Src\Models\Suscription as Suscription;


class FilesController {
    private  $db;
    private  $Spreadsheet;
    private  $uploads;
    public function __construct(ContainerInterface $c){
        $this->db = $c->get('db');
        $this->uploads = $c->get('settings')['uploads'];
        $this->Spreadsheet = $c->get(Spreadsheet::class);
    }
    /**
     * saveFile
     *
     * Subida de archivos a la plataforma
     *
     * @param Type $var Description
     * @return type
     * @throws conditon
    **/
    public function saveFile(Request $request,Response $response, array $args): Response{
        $data 			= $request->getParsedBody();
        $filesUploaded  = $request->getAttribute('filesUploaded');
        $arrayFilesSave = [];
        //$filesIds = DB::table('archivos')->insertGetId($filesUploaded, 'id_archivo');

        $filesSave = Files::create($filesUploaded);
        
        $arrayFilesSave = $filesSave->toArray();
      
        
        if (count($arrayFilesSave) == 0) {
            $response->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'error'=>'Error al subir el archivo'
            )));
        }

        if (count($arrayFilesSave) > 0) {
            $response->getBody()->write(json_encode(array(
                'statusCode' => 200,
                'data'=> $arrayFilesSave,
                'description'=> 'El archivo se subió con éxito.'
            )));
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getFilesGroup(Request $request, Response $response, array $args):Response  {
        $data = $request->getQueryParams();
        $files = explode(",", $data['files']);
        
        $query = Files::select(
            'id_archivo',
            'nombre_archivo',
            'ruta_archivo',
            'tipo_archivo',
            'extension_archivo',
            'tamano_archivo',
            'fecha_subida_archivo',
        )
        ->whereIn('id_archivo',$files)
        ->get();
        //var_dump($this->SqlHelper->toSql($query));
        if (!$query) {

            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'error'=> 'No se encontraron archivos!'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }

        //devolvemos todos los resultados
        $res = $response->withStatus(200);
        $res->getBody()->write(json_encode(array(
            'statusCode'        => 200,
            'files'           => $query->toArray(),
        )));

        return $res->withHeader('Content-Type', 'application/json');
    }
    public function getStreamFile(Request $request, Response $response, array $args):Response  {
        
        $filename = $args['filename'];
        $path = $args['path'];

        
        //$filePath = $this->uploads['path'] .'/'. $filename;
        $filePath =  __DIR__ . "/../../uploads/"."{$path}/" . $filename;
        //$filePath =  '/home/dacosta/proyectos/miembros/miembros/uploads/'.$filename.'.pdf';
        if (file_exists($filePath)) {
            $fileStream = fopen($filePath, 'r');
            $response = $response->withHeader('Content-Type', mime_content_type($filePath));
            return $response->withBody(new \Slim\Psr7\Stream($fileStream));
        } else {

            $res = $response->withStatus(404);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 404,
                'error'=> 'Archivo no encontrado :('
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }

    }
    /**
     * deleteFile
     *
     * Eliminar el archivo de la base de datos y físico 
    **/
    public function deleteFile(Request $request,Response $response, array $args): Response{
        //$data = $request->getParsedBody();
        $fileId = $args['id'];

        $query = Files::where('id_archivo',$fileId)->first();//->delete();
        
        $file = $query->toArray();

        if (count($file) == 0) {
            $res =$response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'error'=>'El archivo ya no éxiste!'
            )));
        }
        $pathFile = "{$this->uploads['path']}/{$file['ruta_archivo']}";
    
        try {
            if(!is_writable($pathFile)){
                throw new Exception('No tiene permisos problema de servidor!');
            }

            unlink($pathFile);
            $res =$response->withStatus(200);
            $res->getBody()->write(json_encode(array(
                'statusCode' => 200,
                'description'=>'Archivo eliminado con éxito!',
            )));
            //se elimina el archivo de la base de datos
            $query->delete();
        }
        catch(Exception $e) {
            $res =$response->withStatus(400);
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'error'=>'El archivo no se pudo eliminar!',
                'description'=>$e->getMessage()
            )));
        }

        return $res->withHeader('Content-Type', 'application/json');
    }

    public function getCodeUser($code) : String {

        $numberRandom = rand(1000, 9999);
        return "{$code}{$numberRandom}";
    }
}