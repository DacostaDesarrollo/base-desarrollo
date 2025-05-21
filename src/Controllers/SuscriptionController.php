<?php
namespace Src\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Illuminate\Database\Capsule\Manager as DB;

use Src\Models\Suscription as Suscription;

class SuscriptionController {
    public function __construct(){}

    /**
     * Guardar suscripción usuario
     */
    public function saveSuscription(Request $request, Response $response, array $args):Response  {
        
        $data = $request->getParsedBody();
        //validamos que los datos llegen con valores
        if (
            empty($data['producto_suscripcion']) || 
            empty($data['id_usuario_suscripcion'])) {
           
            $res = $response->withStatus(404);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 404,
                'error'=> 'Faltan datos para crear la inscripción'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }
        $date = date('Y-m-d H:i:s');
        $savesus = Suscription::create([
            'producto_suscripcion' => $data['producto_suscripcion'],
            'id_usuario_suscripcion' => $data['id_usuario_suscripcion'],
            'estado_pago_suscripcion' => 0,
            'fecha_suscripcion' => $date,
            //fecha actual con un año más
            'fecha_fin_suscripcion' => date("Y-m-d H:i:s", strtotime($date. " +1 year")),
        ]);

        $response->getBody()->write(json_encode(array(
            'statusCode' => $response->getStatusCode(),
            'description'=>'¡Suscripción creada correctamente!',
            'suscripcion'=> $savesus->toArray()
        )));

        return $response->withHeader('Content-Type', 'application/json');
    } 
    /**
     * Actualizar el estado de la postulación
     */
    public function updateStatusSuscription(Request $request, Response $response, array $args):Response  {
        $data           = $request->getParsedBody();
        $idSuscription  = (int) $args['id'];
        $status         = (int) $data['status'];

        $updateQuery = Suscription::select('id_suscripcion','producto_suscripcion','estado_pago_suscripcion')
        ->where('id_suscripcion', $idSuscription);

        $updateQuery->update(['estado_pago_suscripcion' => $status]);
        $dataSuscription = $updateQuery->get();

        $response->getBody()->write(json_encode(array(
            'statusCode' => $response->getStatusCode(),
            'description' => '¡El estado de la postulación se actualizo con éxito!',
            'suscripcion'=> $dataSuscription
        )));

        return $response->withHeader('Content-Type', 'application/json');

    }
}