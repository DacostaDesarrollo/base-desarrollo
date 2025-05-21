<?php
namespace Src\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Illuminate\Database\Capsule\Manager as DB;

use Src\Models\Invoice as Invoice;
use Src\Models\InvoiceDetails as InvoiceDetails;
use Src\Models\Product as Product;
use Src\Models\SuscriptionInvoiceDetails as SuscriptionInvoiceDetails;


class InvoiceController {
    public function __construct(){}

    public function getInvoice(Request $request, Response $response, array $args):Response  {
        $invoice = $args['id_invoice'];

        $query = Invoice::where('id_factura', $invoice)
        //->join('usuarios', 'usuarios.id_usuario', '=', 'facturas.id_usuario_fk')
        ->first();

        if (!$query) {

            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'error'=> 'No se encontro la factura!'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }

        //devolvemos todos los resultados
        $res = $response->withStatus(200);
        $res->getBody()->write(json_encode(array(
            'statusCode'        => 200,
            'factura'           => $query->toArray(),
        )));

        return $res->withHeader('Content-Type', 'application/json');
    }
    public function listInvoices(Request $request, Response $response, array $args){
        $params = $request->getQueryParams(); // Obtén el número de página de los parámetros de la URL
        $user = $request->getAttribute('user');

        //Paginación
        $perPage = isset($params['perPage'])?$params['perPage']:15; // Número de elementos por página
        $page = isset($params['page'])?$params['page']:1; // Página actual

        $offset = ($page - 1) * $perPage; // Calcula el desplazamiento
        
        //inicializar la query
        $invoices = Invoice::query();
        $invoices->leftJoin('factura_detalles', 'facturas.id_factura', '=', 'factura_detalles.id_factura_fk');
        $invoices->leftJoin('suscripcion_factura_detalle', 'factura_detalles.id_factura_detalle', '=', 'suscripcion_factura_detalle.id_detalle_factura_fk');
        $invoices->leftJoin('suscripciones', 'suscripcion_factura_detalle.id_suscripcion_fk', '=', 'suscripciones.id_suscripcion');
        $invoices->leftJoin('proyectos', 'suscripciones.id_suscripcion', '=', 'proyectos.id_suscripcion_premio');
        //Busqueda por nombre o apellido
        if (!empty($params['query']) && $params['query'] != "null") {
        
            $invoices->where(function ($query) use ($params) {
                $query->orWhere('proyectos.codigo_proyecto', 'LIKE', '%' . $params['query'] . '%');
                $query->orWhere('proyectos.nombre_proyecto', 'LIKE', '%' . $params['query'] . '%');
            });
        }

        if (isset($params['status']) && $params['status'] != "null") {
            $invoices->where('estado_factura', '=',$params['status']);
        }
         //si eres usuario y no administrador
        if (isset($user->tipo_usuario_fk) && $user->tipo_usuario_fk != 'admin') {
            $invoices->where('suscripciones.id_usuario_suscripcion', '=',$user->id_usuario);
        }
    
        
        
        $invoices->select(
            //facturas
            'facturas.id_factura',
            'facturas.codigo_factura',
            'facturas.descripcion_factura',
            'facturas.estado_factura',
            'facturas.valor_total_factura',
            'facturas.email_factura',
            //suscripción
            'suscripciones.id_suscripcion',
            'suscripciones.estado_pago_suscripcion',
            'suscripciones.id_usuario_suscripcion',
            'suscripciones.fecha_suscripcion',
            'suscripciones.fecha_fin_suscripcion',
            //proyectos
            'proyectos.id_proyecto',
            'proyectos.codigo_proyecto',
            'proyectos.nombre_proyecto',
            'proyectos.codigo_membresia_proyecto'
        );
        $invoices->offset($offset);
        $invoices->limit($perPage);
        $invoices->orderBy('facturas.id_factura', 'desc');
        $invoicesData = $invoices->get();
        
        if (empty($invoicesData->toArray())) {
            
            $res = $response->withStatus(404);
            
            $res->getBody()->write(json_encode($invoicesData));

            return $res->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($invoicesData));
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function saveInvoice(Request $request, Response $response, array $args):Response  {
    
        $data = $request->getParsedBody();

        //validamos que los datos llegen con valores
        if (
            empty($data['id_suscripcion']) ||
            empty($data['descripcion_factura']) ||
            empty($data['producto_factura_detalle']) ||
            empty($data['id_usuario_fk']) ||
            empty($data['facturar_a_factura']) ||
            empty($data['identificacion_factura']) ||
            //empty($data['ciudad_factura']) ||
            empty($data['pais_factura']) ||
            //empty($data['direccion_factura']) ||
            empty($data['email_factura'])
        ) {
           
            $res = $response->withStatus(404);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 404,
                'error'=> 'Faltan datos para crear la factura'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }

        $date = date('Y-m-d H:i:s');
    
        $saveInvo = Invoice::create([
            //'id_factura'            =>  null,
            'codigo_factura'        => "{$data['id_usuario_fk']}-{$data['producto_factura_detalle']}-{$date}",
            'descripcion_factura'   => $data['descripcion_factura'],
            'id_usuario_fk'         => $data['id_usuario_fk'],
            'facturar_a_factura'    => $data['facturar_a_factura'],
            'identificacion_factura'=> $data['identificacion_factura'],
            'ciudad_factura'        => $data['ciudad_factura'],
            'pais_factura'          => $data['pais_factura'],
            'direccion_factura'     => $data['direccion_factura'],
            'email_factura'         => $data['email_factura'],
            'estado_factura'        => 0,
            'valor_total_factura'   => 0,
            'fecha_pago_factura'   => null,
            'id_pago_fk'   => null,
            'comentarios_factura'   => '',
            //'comentarios_factura'   => null,
            'fecha_creacion_factura'=> $date,
            'fecha_actualizacion_factura'=> $date
        ]);

        $invoiceData = $saveInvo->toArray();

        //validamos que haya creado la factura
        if (empty($invoiceData)) {
            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'error'=> 'No se creo la factura!'
            )));
            
            return $res->withHeader('Content-Type', 'application/json');
        }

        //consultar los productos o membresias
        $queryProduct = Product::select('id_producto','slug_producto','valor_producto')
        ->where('slug_producto', $data['producto_factura_detalle'])
        ->first();
        
        $productData = $queryProduct->toArray();

        if (!$productData) {

            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'error'=> 'Este producto no existe!'
            )));
            
            return $res->withHeader('Content-Type', 'application/json');
        }

        //guardamos el detalle de la factura
        $saveInvoDetail = InvoiceDetails::create([
            'id_factura_fk'             => (int) $saveInvo->id_factura,
            'producto_factura_detalle'  => (string) $productData['slug_producto'],
            'cantidad_producto_detalle' => 1,
            'valor_unitario_detalle'    => (int) $productData['valor_producto'],
            'valor_facturado_detalle'   => (int) $productData['valor_producto'],
        ]);
        
        $dataInvoiceDetail = $saveInvoDetail->toArray();
        
        //generamos registro de la relación suscripción factura detalle
        $suscriptionInvoiceDetailsSave = SuscriptionInvoiceDetails::create([
            'id_suscripcion_fk'         => (int) $data['id_suscripcion'],
            'id_detalle_factura_fk'     => (int) $dataInvoiceDetail['id_factura_detalle']
        ]);


        //actualizamos el valor de la factura en la base de datos
        $updateAmount = $this->updateAmountInvoise($saveInvo->id_factura,$productData['valor_producto']);
        
        //devolvemos todos los resultados
        $res = $response->withStatus(200);
        $res->getBody()->write(json_encode(array(
            'statusCode'            => 200,
            'description'           => "Factura creada con éxito!",
            'factura'               => $invoiceData,
            'detalle_factura'       => $dataInvoiceDetail,
            'suscripcion_detalle'   => $suscriptionInvoiceDetailsSave->toArray(),
            'producto'              => $productData
        )));

        return $res->withHeader('Content-Type', 'application/json');
    }

    private function updateAmountInvoise(int $idInvoise, float $amount) {
        // Actualiza el nombre directamente en la base de datos
        return Invoice::where('id_factura', $idInvoise)->update(['valor_total_factura' => $amount]);
    }
}