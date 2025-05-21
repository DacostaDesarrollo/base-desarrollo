<?php
namespace Src\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Illuminate\Database\Capsule\Manager as DB;

use Src\Models\PaymentsMethod as PaymentsMethod;
use Src\Models\Payments as Payments;
use Src\Models\Invoice as Invoice;
use Src\Models\Suscription as Suscription;

class PaymentsController {
    private $payments;
    private $twig;
    
    public function getInvoiseCode(string $invoiceCode){
        $query = Invoice::where('codigo_factura',$invoiceCode)
        ->first();

        return  $query->toArray();
    }
    
    public function __construct($c, $payments,$twig){
        $this->payments = $payments;
        $this->twig = $twig;
        //si es uno esta en pruebas
        if($this->payments['payu']['test'] == 1){
            $this->payments= [
                'payu'=>[
                    'test'      => $this->payments['payu']['test'],
                    'merchantId'=> 508029,
                    'accountId' => 512321,
                    'action'    => 'https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu',
                    'apikey'    => '4Vj8eK4rloUd272L48hsrarnUA',
                    'responseUrl' => 'https://premios.clicclatam.org/sistema/api/payments/method/payu/responseUrl',
                    'confirmationUrl' => 'https://premios.clicclatam.org/sistema/api/payments/method/payu/confirmationUrl',
                ]
            ];
        }
        
    }

    /**
     * Url para la confirmación de payu
    */ 
    public function payuResponsePaymentRequest(Request $request, Response $response, array $args) : Response {
        $params = $request->getQueryParams(); 

        $ApiKey = $this->payments['payu']['apikey'];
        $merchant_id = $params['merchantId'];
        $referenceCode = $params['referenceCode'];
        $TX_VALUE = $params['TX_VALUE'];
        $New_value = number_format($TX_VALUE, 1, '.', '');
        $currency = $params['currency'];
        $transactionState = $params['transactionState'];
        $firma_cadena = "$ApiKey~$merchant_id~$referenceCode~$New_value~$currency~$transactionState";
        $firmacreada = md5($firma_cadena);
        $firma = $params['signature'];
        $reference_pol = $params['reference_pol'];
        $cus = $params['cus'];
        $extra1 = $params['description'];
        $pseBank = $params['pseBank'];
        $lapPaymentMethod = $params['lapPaymentMethod'];
        $transactionId = $params['transactionId'];


        if ($_REQUEST['transactionState'] == 4 ) {
            $estadoTx = "Transacción aprobada";
        }

        else if ($_REQUEST['transactionState'] == 6 ) {
            $estadoTx = "Transacción rechazada";
        }

        else if ($_REQUEST['transactionState'] == 104 ) {
            $estadoTx = "Error";
        }

        else if ($_REQUEST['transactionState'] == 7 ) {
            $estadoTx = "Pago pendiente";
        }

        else {
            $estadoTx=$_REQUEST['mensaje'];
        }
        
        return $this->twig->render($response, 'payments/resppayments.html', [
            'estadoTx'      => $estadoTx,
            'referenceCode' => $referenceCode,
            'currency'      => $currency,
            'extra1'        => $extra1,
            'New_value'     => $New_value,
            'firma'         => $firma,
            'firmacreada'     => $firmacreada,
            'transactionId'     => $transactionId,
            'reference_pol'     => $reference_pol,
            'referenceCode'     => $referenceCode,
            'lapPaymentMethod'     => $lapPaymentMethod,
        ]);
    }

    private function updateStatusInvoise(string $codeInvoise,int $id_pago_fk, int $status) {
        
        return Invoice::where('codigo_factura', $codeInvoise)->update([
            'id_pago_fk' => $id_pago_fk,
            'estado_factura' => $status,
            'fecha_pago_factura' => date('Y-m-d H:i:s'),
            //'fecha_actualizacion_factura' => date('Y-m-d H:i:s'),
        ]);
    }

    private function updateSuscriptionStatus(int $idInvoice, int $status) {
        return Suscription::where('factura_detalles.id_factura_fk', $idInvoice)
        ->join('suscripcion_factura_detalle', 'suscripciones.id_suscripcion', '=', 'suscripcion_factura_detalle.id_suscripcion_fk')
        ->join('factura_detalles', 'suscripcion_factura_detalle.id_detalle_factura_fk', '=', 'factura_detalles.id_factura_detalle')
        ->update(['suscripciones.estado_pago_suscripcion' => $status]);
    }

    public function payuConfirmPaymentRequest(Request $request, Response $response, array $args) : Response {
        $data = $request->getParsedBody();
        $arrayObjectPayment = json_encode($data);
        $invoice = null;
        $myfile = fopen("payuPayments.txt", "a") or die("Unable to open file!");
        ob_start();
        print_r($_POST);
        $contenido = ob_get_contents();
        ob_end_clean();
        fwrite($myfile,$contenido);
        fclose($myfile);

        if (!isset($data['reference_sale'])) {
            
            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'error'=> 'No es un pago!'
            )));
            
            return $res->withHeader('Content-Type', 'application/json');
            
        }

        //consultamos los datos de la factura para actualizar la inscripción
        $invoice = $this->getInvoiseCode($data['reference_sale']);

        if($data['state_pol'] == 4){
            $estado_pago = 1;
        }
        if($data['state_pol'] == 6 or $data['state_pol'] == 5){
            $estado_pago = 0;
        }

        $arrayReferenceSale = explode("-", $data['reference_sale']);

        //creamos el registro del pago
        $date = date('Y-m-d H:i:s');
        $savePay = Payments::create([
            'moneda_pago' => $data['currency'],
            'pasarela_pago_fk' => 1,
            'valor_pago' => $data['value'],
            'fecha_pago' => $date,
            'estado_pago_pasarela' => $data['state_pol'],
            'codigo_venta_pasarela' => $data['reference_sale'],
            'iva_pago' => $data['tax'],
            'arreglo_pago_pasarela' => $arrayObjectPayment,
            'metodo_pago_pasarela' => $data['payment_method_name'],
            'tipo_metodo_de_pago_pasarela' => $data['payment_method_type'],
            'estado_pago' => $estado_pago,
            'id_usuario_pago_fk' => $arrayReferenceSale[0],
        ]);
        $dataPay = $savePay->toArray();
        
        if (!empty($dataPay)) {
            /**
             * Actualizar el estado del pago en la factura y relacionar con el id de pago
             */
            $this->updateStatusInvoise((string) $dataPay['codigo_venta_pasarela'],(int) $dataPay['id_pago'],(int)$dataPay['estado_pago']);
            
            //actualizamos el estado de la suscripción
            $this->updateSuscriptionStatus($invoice['id_factura'],$dataPay['estado_pago']);
        }

        $response->getBody()->write(json_encode(array(
            'statusCode' => $response->getStatusCode(),
            'suscripcion'=> $dataPay
        )));

        return $response->withHeader('Content-Type', 'application/json');

    }

    private function generateSignaturePayu(string $referenceCode,$amount,string $currency) : string {
        $apikey = $this->payments['payu']['apikey'];
	    $merchantId = $this->payments['payu']['merchantId'];

 	    return md5($apikey.'~'.$merchantId.'~'.$referenceCode.'~'.$amount.'~'.$currency);
    }

    public function getFormMethod(Request $request, Response $response, array $args):Response{
        $data = $request->getParsedBody();
        $method = $args['id_pasarela'];
        $currency = 'USD';
        $amount = $data['valor_total_factura'];
        $lng = 'es';
        //Evaluar si hay que modificar el pago por el país
        $tax_percent = 19;
        
        if($tax_percent > 0){
            $variable = 1;
            $divide =  (float)$variable.'.'.$tax_percent;
            $tax = $amount - ($amount / $divide);
            $taxReturnBase = $amount - ($amount / $divide);
        }
      
        if($currency == 'USD'){
            $tax = number_format($tax, 2, '.', '');
            $taxReturnBase = number_format($tax, 2, '.', '');
        }

        $signature =  $this->generateSignaturePayu($data['codigo_factura'],$amount,$currency);
        
        $dataForm =   array(
            'merchantId' => $this->payments['payu']['merchantId'],
            'accountId'=> $this->payments['payu']['accountId'],
            'description'=> $data['descripcion_factura'],
            'referenceCode'=> $data['codigo_factura'],
            'amount'=> $amount,
            'test'=> $this->payments['payu']['test'],
            'tax'=> $tax,
            'taxReturnBase' => $taxReturnBase, 
            'currency'=> $currency,
            'lng'=> $lng,
            'responseUrl'=> $this->payments['payu']['responseUrl'],
            'confirmationUrl'=> $this->payments['payu']['confirmationUrl'],
            'action'=> $this->payments['payu']['action'],
            'signature'=>$signature,
            'buyerEmail'=> $data['email_factura'],
            'buyerFullName'=> "{$data['facturar_a_factura']}",
        );

        $response->getBody()->write(json_encode(array(
            'statusCode' => $response->getStatusCode(),
            'form'=> $dataForm,
        )));

        return $response->withHeader('Content-Type', 'application/json');
    }
    public function getMthodsPayments(Request $request, Response $response, array $args):Response{
        
        //consultar los productos o membresias
        $methods = PaymentsMethod::select('id_pasarela','nombre_pasarela')
        ->get();
        
        $methodsData = $methods->toArray();

        if (empty($methodsData)) {
           
            $res = $response->withStatus(404);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 404,
                'error'=> 'No hay pasarelas de pago!'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode(array(
            'statusCode' => $response->getStatusCode(),
            'methods'=> $methodsData
        )));

        return $response->withHeader('Content-Type', 'application/json');


    }
}