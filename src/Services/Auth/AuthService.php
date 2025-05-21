<?php
declare(strict_types=1);
namespace Src\Services\Auth;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Pagination\Paginator;
use Src\Models\Usuario as Usuario;

use Src\Helpers\JwtHelper as JwtHelper;
use Src\Helpers\SqlHelper as SqlHelper;
use Respect\Validation\Exceptions\ValidationException as ValidationException;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;

class AuthService {
    private $JwtHelper;
    private $SqlHelper;
    private string $secretKey;
    private $emailService;

    public function __construct(string $secretKey, $emailService){
        if (empty($secretKey)) {
            throw new \InvalidArgumentException('La clave secreta no puede estar vacía');
        }
        $this->secretKey = $secretKey;
        $this->emailService = $emailService;
        $this->JwtHelper = new JwtHelper();
        $this->SqlHelper = new SqlHelper();
    }
    /** 
     * Login Autenticar ususarios de la plataforma
     */
    public function login(Request $request, Response $response, array $args):Response {
        $data = $request->getParsedBody();
        $password = md5($data['password_usuario']);

        $queryUser = Usuario::select(
            'id_usuario',
            'identificacion_tributaria_usuario',
            'nombre_usuario', 
            'apellido_usuario',
            'email_usuario',
            'tipo_usuario_fk',
            'tipo_usuarios.nombre_tipo',
            'tipo_usuarios.slug_tipo'
        )
        //or sentecias multiples con parentesis
        ->join('tipo_usuarios', 'usuarios.tipo_usuario_fk', '=', 'tipo_usuarios.slug_tipo')
        ->where(function ($query) use ($data) {
            $query
            ->orWhere('identificacion_tributaria_usuario', $data['documento_usuario'])
            ->orWhere('email_usuario', $data['documento_usuario']);
        })
        ->where('password_usuario', $password)
        ->where('estado_usuario',1)
        ->first();

       //var_dump($this->SqlHelper->toSql($queryUser));
        
        if(!$queryUser){
            
            $res = $response->withStatus(404);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 404,
                'error'=> 'Documento, código o contraseña incorrecto.'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }
        
        $token = $this->JwtHelper->getTokenJWT($this->secretKey,$queryUser->toArray(),"+3 hour");

        $response->getBody()->write(json_encode(array(
            'statusCode' => $response->getstatusCode(),
            'ususario'=> $queryUser->toArray(),
            'token'=> $token
        )));

        return $response->withHeader('Content-Type', 'application/json');

    }
    /** 
     * Login Autenticar ususarios de miembros
     */
    public function loginMember(Request $request, Response $response, array $args):Response {
        $data = $request->getParsedBody();
        $password   = $data['password_usuario'];
        $user       = $data['documento_usuario'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://miembros.clicclatam.org/api/auth/member/login',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => "documento_usuario={$user}&password_usuario={$password}",
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded'
        ),
        ));

        $responseRequest = curl_exec($curl);

        curl_close($curl);

        $responseData = json_decode($responseRequest);
  
        $res = $response->withStatus($responseData->statusCode);
        
        $res->getBody()->write(json_encode($responseData));

        return $res->withHeader('Content-Type', 'application/json');
       
    }
    /**
     * Registro de usuarios
     */
    public function register(Request $request, Response $response, array $args):Response {
        $data = $request->getParsedBody();
        
        try {
            $user = Usuario::validateAndCreateUserAdmin($data);
        } catch (ValidationException $e) {
            
            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'description'=> 'Error al crear el usuario.',
                'error'=> (array) array_values($e->getMessages()),
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode(array(
            'statusCode' => $response->getstatusCode(),
            'description'=> 'Usuario creado con éxito',
            'ususario'=> $user->toArray(),
        )));

        return $response->withHeader('Content-Type', 'application/json');

    }
    /**
     * Recuperar contraseña
     */
    public function forgotPassword(Request $request, Response $response, array $args):Response {
        $data = $request->getParsedBody();
        $dataEmail = [];
        $email = (isset($data['email_usuario']))?$data['email_usuario']:null;

        if ($email == null || $email == '') {
            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'description'=> 'Debes ingresar un correo valido.'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }
        
        $existUser = Usuario::select('id_usuario','nombre_usuario','email_usuario')
            ->where('email_usuario', $email)
            ->first();
     
        /**
         * Si no existe el usuario se hace la devolución
         */
        if (!$existUser) {
            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'description'=> 'El usuario no existe en el sistema.'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }

        $user = $existUser->toArray();
        $token = $this->JwtHelper->getTokenJWT($this->secretKey,$user, '+1 hour');
        
        //actualizar token en la tabla usuarios
        $existUser->token_usuario = $token;
         
        $resUpdate = $existUser->save();
        
        if (!$resUpdate ) {

            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'description'=> 'El usuario no actualizo la solicitud de recuperar contraseña.'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }
    
        $user['token'] = $token;
        
        $email = $this->emailService->sendEmail(
            $user['email_usuario'], 
            null,
            'Solicitud de recuperación de contraseña miembros',
            null,
            true,
            'email/forgotpass.html',
            $user,
            null,
        ); 
        
        if ($email['statusCode'] == 200) {
            # code...
            $res = $response->withStatus(200);
            $res->getBody()->write(json_encode(array(
                'statusCode' => $response->getstatusCode(),
                'description' => 'Hemos enviado un correo con las instrucciones para recuperar la contraseña!',
                'ususario'=> $user,
                'email'=>$email,
            )));

        }else{
            $res = $response->withStatus(400);
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'description' => 'El correo no pudo enviarse!',
                'ususario'=> $user,
                'email'=>$email,
            )));
        }


        return $res->withHeader('Content-Type', 'application/json');
    
    }
    public function newPassword(Request $request, Response $response, array $args):Response {
        $data   = $request->getParsedBody();
        $user   = $request->getAttribute('user');
        $params = $request->getQueryParams();

        if (empty($data['password_usuario'])) {
            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'description'=> 'Debes ingresar un una contraseña'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }

        if (empty($data['token'])) {
            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'description'=> 'No hay un token!'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }

        $existUser = Usuario::select('id_usuario','nombre_usuario','email_usuario','token_usuario')
            ->where('id_usuario', $user->id_usuario)
            ->first()->toArray();
     
        
        if (!$existUser) {
            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'description'=> 'El usuario no éxiste.'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }
        
        if($data['token'] != $existUser['token_usuario']){
            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'description'=> 'El token no coinside o ya expiró.'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }
        $md5 = md5($data['password_usuario']);
        
        $userQuery = Usuario::select('id_usuario','nombre_usuario','email_usuario','token_usuario','password_usuario')
            ->where('id_usuario', $user->id_usuario)->first()
            ->update(['password_usuario' => $md5]);
        

        if (!$userQuery) {
            $res = $response->withStatus(400);
            
            $res->getBody()->write(json_encode(array(
                'statusCode' => 400,
                'description'=> 'Error al cambiar tu contraseña!'
            )));

            return $res->withHeader('Content-Type', 'application/json');
        }

        $res = $response->withStatus(200);
        $res->getBody()->write(json_encode(array(
                'statusCode' => $response->getstatusCode(),
                'description' => 'Hemos cambiado tu contraseña con éxito!',
                //'ususario'=> $user,
        )));


        return $res->withHeader('Content-Type', 'application/json');
    
    }

    public function getCodeUser($code) : String {

        $numberRandom = rand(1000, 9999);
        return "{$code}{$numberRandom}";
    }

    public function generateToken(array $userData): string
    {
        $issuedAt = time();
        $expire = $issuedAt + 3600; // 1 hora

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'data' => $userData
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function validateToken(string $token): object
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return $decoded->data;
        } catch (InvalidArgumentException $e) {
            throw new \Exception('Token inválido: clave secreta malformada');
        } catch (DomainException $e) {
            throw new \Exception('Token inválido: algoritmo no soportado');
        } catch (SignatureInvalidException $e) {
            throw new \Exception('Token inválido: firma incorrecta');
        } catch (BeforeValidException $e) {
            throw new \Exception('Token inválido: aún no válido');
        } catch (ExpiredException $e) {
            throw new \Exception('Token expirado');
        } catch (UnexpectedValueException $e) {
            throw new \Exception('Token malformado');
        }
    }

    public function sendPasswordResetEmail(string $email, string $resetToken): void
    {
        $resetLink = "https://premios.clicclatam.org/sistema/reset-password?token=" . $resetToken;
        
        $this->emailService->sendEmail(
            $email,
            'Recuperación de Contraseña',
            'reset-password',
            ['resetLink' => $resetLink]
        );
    }
}