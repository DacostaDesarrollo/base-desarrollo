<?php 
namespace Src\Services;
/**
* Gestión de correos
*/
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as Exception;


class EmailService extends PHPMailer{
	private $twig;
	function __construct(array $smtp,$twig){
        $this->twig = $twig;
		$this->configurarSmtp($smtp);
	}
	/**
	 * Configuración smtp
	*/
	private function configurarSmtp(array $smtp){
		//Tell PHPthiser to use SMTP
		try {
			$this->isSMTP();
			//Enable SMTP debugging
			// 0 = off (for production use)
			// 1 = client messages
			// 2 = client and server messages
			$this->SMTPDebug = 0;
			//Ask for HTML-friendly debug output
			$this->Debugoutput = 'html';
			//Set the hostname of the this server
			$this->Host = $smtp['host'];
			// use
			// $this->Host = gethostbyname('smtp.gthis.com');
			// if your network does not support SMTP over IPv6
			//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
			$this->Port = $smtp['port'];
			//Set the encryption system to use - ssl (deprecated) or tls
			$this->SMTPSecure = isset($smtp['SMTPSecure'])?$smtp['SMTPSecure']:null;
			//Whether to use SMTP authentication
			$this->SMTPAuth = true;
			//Username to use for SMTP authentication - use full ethis address for gthis
			$this->Username = $smtp['username'];
			//Password to use for SMTP authentication
			$this->Password = $smtp['password'];
			//Set who the message is to be sent from
			$this->setFrom('miembros@clicclatam.org', 'Miembros');


			// Active condition utf-8
			$this->CharSet = 'UTF-8';

			
			//Set an alternative reply-to address
			//$this->addReplyTo('dacostadesarrollo@gmail.com', 'First Last');
			//Set who the message is to be sent to
			//$this->addAddress('dacostadesarrollo@gmail.com', 'John Doe');


		} catch (Exception $e) {
			
			return $e->errorMessage(); //Pretty error messages from PHPMailer
		}
		
	}
	/**
	 * EnviarEmail
	 * @param  [String]
	 * @param  [String]
	 * @param  [type]
	 * @return [type]
	 */
	public function sendEmail(
		$email,
		$email2,
		$asunto,
		$mensaje=null,
		$html=true,
		$plantilla=null,
		$arrayData=null,
		$reenviar = null
		){

		$this->addAddress($email,'');

		if($email2 != null){
			$this->addAddress($email2,'');
		}


		if($reenviar != null){
			$this->addAddress('eventos@clicclatam.org ','');
		}

		$this->Subject = $asunto;
		if ($html) {
			//$mensaje = ($plantilla == false)? $mensaje: renderPlantilla($plantilla,$arrayData,true);
			$mensaje = $this->twig->fetch($plantilla, $arrayData);

			//$this->msgHTML  = $mensaje;
			$this->Body 	= $mensaje;
			$this->isHTML($html);
			
		}else{
			$this->Body  = $mensaje;
		}
		try{
			if (!$this->send()) {
				return array(
					'statusCode'=> 500,
					'description'=>'Error al enviar el correo',
					'error'=> $this->ErrorInfo
				);
			} else {
				return array(
					'statusCode'=> 200,
					'description'=>'El correo se envio con éxito',
				);
			}
		}catch(Exception $e){
				return array(
					'statusCode'=> 200,
					'description'=>'El correo se envio con éxito',
				);
		}
	}
	
}

 ?>