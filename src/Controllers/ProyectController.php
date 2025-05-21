<?php
declare(strict_types=1);
namespace Src\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Illuminate\Database\Capsule\Manager as DB;
use Src\Models\Proyect as Proyect;

use Src\Helpers\JwtHelper as JwtHelper;
use Src\Helpers\SqlHelper as SqlHelper;
use Respect\Validation\Exceptions\ValidationException as ValidationException;


class ProyectController {
    private $JwtHelper;
    private $SqlHelper;
    private string $secretKey;
    private $emailService;

    public function __construct(string $secretKey,$emailService){
        $this->secretKey = $secretKey;
        $this->emailService = $emailService;
        $this->JwtHelper = new JwtHelper();
        $this->SqlHelper = new SqlHelper();
    }
   
    public function getStatusProyectsMember(Request $request, Response $response, array $args):Response  {
        $memberId = $args['id_usuario'];
        $awardsId = $args['id_premio'];

        $query = Proyect::select(
            'suscripciones.id_usuario_suscripcion',
            'suscripciones.id_suscripcion',
            'suscripciones.limite_postulaciones',
            'proyectos.id_proyecto',
            'proyectos.nombre_proyecto',
            'premios.id_premio',
            'premios.nombre_premio',
            'premios.fecha_limite_postulaciones_premio',
        )
        ->where('suscripciones.id_usuario_suscripcion', $memberId)
        ->where('proyectos.id_premio_proyecto', $awardsId)
        ->where('suscripciones.estado_pago_suscripcion', 1)
        ->join('suscripciones', 'proyectos.id_suscripcion_premio', '=', 'suscripciones.id_suscripcion')
        ->join('premios', 'proyectos.id_premio_proyecto', '=', 'premios.id_premio')
        ->get();

        if (empty($query->toArray())) {

            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'error'=> 'No se encontraron proyectos!'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }

        //devolvemos todos los resultados
        $res = $response->withStatus(200);
        $res->getBody()->write(json_encode(array(
            'statusCode'   => 200,
            'proyects'     => $query->toArray(),
            'count'        => count($query->toArray()),
        )));

        return $res->withHeader('Content-Type', 'application/json');
    }

    /**
     * Registro de Proyecto
    */
    public function saveProyect(Request $request, Response $response, array $args):Response {
        $data = $request->getParsedBody();

        //$token = $this->JwtHelper->getTokenJWT($this->secretKey,$proyect->toArray());
        
        $date = date('Y-m-d H:i:s');
        $saveProyect = Proyect::create([
            'id_premio_proyecto' => $data['id_premio_proyecto'],
            'id_suscripcion_premio' => $data['id_suscripcion'],
            'nombre_organizador_proyecto' => $data['nombre_organizador_proyecto'],
            'cargo_proyecto' => $data['cargo_proyecto'],
            'firma_organizador_proyecto' => $data['firma_organizador_proyecto'],
            'nombre_proyecto' => $data['nombre_proyecto'],
            'entidad_proyecto' => $data['entidad_proyecto'],
            'identificacion_proyecto' => $data['identificacion_proyecto'],
            'telefono_proyecto' => $data['telefono_proyecto'],
            'email_proyecto' => $data['email_proyecto'],
            'web_proyecto' => $data['web_proyecto'],
            'direccion_proyecto' => $data['direccion_proyecto'],
            'ubicacion_proyecto' => $data['ubicacion_proyecto'],
            'codigo_membresia_proyecto' => $data['codigo_membresia_proyecto'],
            'categoria_proyecto' => $data['categoria_proyecto'],
            'subcategoria_proyecto' => $data['subcategoria_proyecto'],
            'resumen_proyecto' => $data['resumen_proyecto'],
            'presentacion_proyecto' => $data['presentacion_proyecto'],
            'ejecucion_proyecto' => $data['ejecucion_proyecto'],
            'direrencial_proyecto' => $data['direrencial_proyecto'],
            'resultado_final_proyecto' => $data['resultado_final_proyecto'],
            'imagenes_proyecto' => serialize($data['imagenes_proyecto']),
            'enlaces_proyecto' => serialize($data['enlaces_proyecto']),
            'documentos_proyecto' => serialize($data['documentos_proyecto']),
            'preguntas_subcategorias_proyecto' => serialize($data['preguntas_subcategorias']),
        ]);
        
        $proyectData = $saveProyect->toArray();
        $codeoProyect = $this->updateCodeProyect($proyectData['id_proyecto'],$proyectData['categoria_proyecto'],date('Y'));
        $proyectData['codigo_proyecto'] = $codeoProyect;
    
        //envio de correo nuevo registro
        $email = $this->emailService->sendEmail(
            $data['email_proyecto'],
            'info@clicclatam.org',
            //'soporte@ideandola.co',
            'Nuevo proyecto clicc',
            null,
            true,
            'proyect/register-proyect.html',
            $proyectData,
            null,
        ); 

        $response->getBody()->write(json_encode(array(
            'statusCode' => $response->getstatusCode(),
            'description'=> 'Postulación creada con éxito!',
            'proyect'=> $proyectData,
            'email'=> $email,
        )));

        return $response->withHeader('Content-Type', 'application/json');

    }

    public function listProyects(Request $request, Response $response, array $args){
        
        $params = $request->getQueryParams(); // Obtén el número de página de los parámetros de la URL
        $user = $request->getAttribute('user');
        
        //Paginación
        $perPage = isset($params['perPage'])?$params['perPage']:15; // Número de elementos por página
        $page = isset($params['page'])?$params['page']:1; // Página actual

        $offset = ($page - 1) * $perPage; // Calcula el desplazamiento
        
        //inicializar la query
        $proyects = Proyect::query();

        $proyects->leftJoin('suscripciones', 'proyectos.id_suscripcion_premio', '=', 'suscripciones.id_suscripcion');
        $proyects->leftJoin('categorias', 'proyectos.categoria_proyecto', '=', 'categorias.id_categoria');
        $proyects->leftJoin('premios', 'proyectos.id_premio_proyecto', '=', 'premios.id_premio');
        //Busqueda por nombre o apellido
        if (!empty($params['query']) && $params['query'] != "null") {
        
            $proyects->where(function ($query) use ($params) {
                $query
                ->where('nombre_proyecto', 'LIKE', '%' . $params['query'] . '%');
                $query->orWhere('nombre_organizador_proyecto', 'LIKE', '%' . $params['query'] . '%');
                $query->orWhere('codigo_proyecto', 'LIKE', '%' . $params['query'] . '%');
                $query->orWhere('email_proyecto', 'LIKE', '%' . $params['query'] . '%');
                $query->orWhere('codigo_membresia_proyecto', 'LIKE', '%' . $params['query'] . '%');
            });
        }

        if (!empty($params['category']) && $params['category'] != "null") {
            
            $proyects->where('categorias.id_categoria', '=',$params['category']);
        }

        if (!empty($params['subcategory']) && $params['subcategory'] != "null") {
            
            $proyects->where('proyectos.subcategoria_proyecto', '=',$params['subcategory']);
        }
    
        //filtra por estado del proyecto
        if (!empty($params['status']) && $params['status'] != "null") {
            $proyects->where('estado_proyecto', '=',$params['status']);
        }

        if (!empty($params['premio']) && $params['premio'] != "null") {
            $proyects->where('id_premio_proyecto', '=',$params['premio']);
        }

        if (!empty($params['winners']) && $params['winners'] != "null") {
            $proyects->where('puntaje_proyecto', '>',0);
        }
        if (isset($user->tipo_usuario_fk) && in_array($user->tipo_usuario_fk,['asociado','adhesion'])) {
            $proyects->where('suscripciones.id_usuario_suscripcion', '=',$user->id_usuario);
        } 

        if (!empty($params['orderBy']) && $params['orderBy'] != "null") {
            $proyects->orderBy($params['orderBy'], 'desc');
        }else{
            //default
            $proyects->orderBy('id_proyecto', 'desc');
        }
        
        //si eres jurado solo puedes llamar proyectos de tu categoría
        if (isset($user->tipo_usuario_fk) && $user->tipo_usuario_fk == 'jurado') {
            
            $proyects->where('estado_proyecto', '=','aprobado');
            $proyects->leftJoin('categorias_jurados', 'proyectos.categoria_proyecto', '=', 'categorias_jurados.id_categoria_categoria_jurado');
            $proyects->where('categorias_jurados.id_jurado_categoria_jurado', '=',$user->id_usuario);
        
        }

        
        $proyects->select(
            'proyectos.*',
            'categorias.id_categoria',
            'categorias.nombre_categoria',
            'premios.id_premio',
            'premios.nombre_premio',
        );
        $proyects->offset($offset);
        $proyects->limit($perPage);
        //$proyects->orderBy('id_proyecto', 'desc');
        //var_dump($this->SqlHelper->toSql($proyects));
        $proyectsData = $proyects->get();
        
        if (empty($proyectsData->toArray())) {
            
            $res = $response->withStatus(404);
            
            $res->getBody()->write(json_encode($proyectsData));

            return $res->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($proyectsData));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function getProyectId(Request $request, Response $response, array $args):Response{
        $idProyect = $args['idProyect'];

        $proyect = Proyect::select(
            'proyectos.*',
            'categorias.id_categoria',
            'categorias.nombre_categoria',
            'categorias.descripcion_categoria',
            'subcategorias.id_categoria as id_subcategoria',
            'subcategorias.nombre_categoria as nombre_subcategoria',
            'subcategorias.descripcion_categoria as descripcion_subcategoria',
        )
        ->where('id_proyecto',$idProyect)
        ->leftJoin('categorias as categorias', 'proyectos.categoria_proyecto', '=', 'categorias.id_categoria')
        ->leftJoin('categorias as subcategorias', 'proyectos.subcategoria_proyecto', '=', 'subcategorias.id_categoria')
        // var_dump($this->SqlHelper->toSql($proyect));
        ->first();

        if (empty($proyect)) {
            
            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'description'=> 'El proyecto no éxiste!'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(array(
            'statusCode' => 200,
            'proyect'=>$proyect
        )));

        
        return $response->withHeader('Content-Type', 'application/json');

    }

    public function deleteProyect(Request $request, Response $response, array $args):Response {
        $idProyect = $args['idProyect'];
        
        $deleteQuery = Proyect::where('id_proyecto', $idProyect)->first();
        
        /**TODO: En un futuro eliminar los archivos que se subieron en este proyecto */

        $resDelete = $deleteQuery->delete();

        if (!$deleteQuery) {
            $res = $response->withStatus(400);
                    
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'error'=> '¡La postulación no se eliminó!',
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }
        
        $res = $response->withStatus(200);
        $res->getBody()->write(json_encode(array(
            'statusCode' => $response->getstatusCode(),
            'description'=> '¡La postulación se elimino con éxito!',
        )));
        
        return $res->withHeader('Content-Type', 'application/json');

    }
    
    private function updateCodeProyect(int $idProyect,$category,$year) {
        // Actualiza el codigo directamente en la base de datos
        $code = "{$year}-{$category}-{$idProyect}";
        $updateCodeProyect = Proyect::where('id_proyecto', $idProyect)->update(['codigo_proyecto' =>  $code]);
        return $code;
    }
    
    public function updateStatusProyect(Request $request, Response $response, array $args):Response {
        $data   = $request->getParsedBody();
        $idProyect = $args['idProyect'];
        $status = $args['status'];


        $updateQuery = Proyect::where('id_proyecto', $idProyect)->update(['estado_proyecto'=>$status]);

        if (!$updateQuery) {
            $res = $response->withStatus(400);
                    
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'description'=> 'El estado de la postulación no se actualizó',
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }
        
        $res = $response->withStatus(200);
        $res->getBody()->write(json_encode(array(
            'statusCode' => $response->getstatusCode(),
            'description'=> 'El estado de la postulación se actualizado con éxito',
        )));
        
        return $res->withHeader('Content-Type', 'application/json');

    }
    /**
     * Actualizar datos de usuarios tipo miembros
     */
    public function updateProyect(Request $request, Response $response, array $args):Response {
        $data   = $request->getParsedBody();
        $idProyect = $args['idProyect'];
        //si viene el categoria_jurado eliminarla por que no se maneja aca

        $updateQuery = Proyect::where('id_proyecto', $idProyect)->update(
            [
            'nombre_organizador_proyecto'=> $data['nombre_organizador_proyecto'],
            'cargo_proyecto'            => $data['cargo_proyecto'],
            'firma_organizador_proyecto'=> $data['firma_organizador_proyecto'],
            'nombre_proyecto'           => $data['nombre_proyecto'],
            'entidad_proyecto'          => $data['entidad_proyecto'],
            'identificacion_proyecto'   => $data['identificacion_proyecto'],
            'telefono_proyecto'         => $data['telefono_proyecto'],
            'email_proyecto'            => $data['email_proyecto'],
            'web_proyecto'              => $data['web_proyecto'],
            'direccion_proyecto'        => $data['direccion_proyecto'],
            'ubicacion_proyecto'        => $data['ubicacion_proyecto'],
            'codigo_membresia_proyecto' => $data['codigo_membresia_proyecto'],
            'categoria_proyecto'        => $data['categoria_proyecto'],
            'subcategoria_proyecto'     => $data['subcategoria_proyecto'],
            'resumen_proyecto'          => $data['resumen_proyecto'],
            'presentacion_proyecto'     => $data['presentacion_proyecto'],
            'ejecucion_proyecto'        => $data['ejecucion_proyecto'],
            'direrencial_proyecto'      => $data['direrencial_proyecto'],
            'resultado_final_proyecto'  => $data['resultado_final_proyecto'],
            'imagenes_proyecto'         => serialize($data['imagenes_proyecto']),
            'enlaces_proyecto'          => serialize($data['enlaces_proyecto']),
            'documentos_proyecto'       => serialize($data['documentos_proyecto']),
            'preguntas_subcategorias_proyecto' => serialize($data['preguntas_subcategorias']),
        ]
        );

        if (!$updateQuery) {
            $res = $response->withStatus(400);
                    
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'description'=> 'El postulación no se actualizó',
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }
        
        $res = $response->withStatus(200);

        $res->getBody()->write(json_encode(array(
            'statusCode' => $response->getstatusCode(),
            'description'=> 'Postulación actualizado con éxito',
        )));
        
        return $res->withHeader('Content-Type', 'application/json');

    }
}