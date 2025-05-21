<?php
namespace Src\Helpers;

use \Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;

class JwtHelper {
    
    public function getTokenJWT($secretKey,array $data,?string $tiempo = "+1 hour"){
        $time = time();
          
        $token = array(
              'iat' => $time, // Tiempo que inició el token
              'exp' => strtotime($tiempo), // Tiempo que expirará el token 30 días
              'data' => $data // información del usuario
        );
        $jwt = JWT::encode($token,$secretKey,'HS256');

        return $jwt;
    }

    private function validar_token(string $token) {

      	$key = $this->secretKey;
      
	  	list($jwt) = sscanf($token, 'Bearer %s');
		
      	if ($jwt != null) {

			try {
				$resJWT = JWT::decode($jwt, new Key($key, 'HS256'))->data;
				return array(
					"statusCode"=> 200,
					"usuario"=> $resJWT
				);

			}  catch (InvalidArgumentException $e) {
					// provided key/key-array is empty or malformed.
			} catch (DomainException $e) {
				// provided algorithm is unsupported OR
				// provided key is invalid OR
				// unknown error thrown in openSSL or libsodium OR
				// libsodium is required but not available.
			} catch (SignatureInvalidException $e) {
				// provided JWT signature verification failed.
				return array(
					'statusCode'=>403,
					'description'=>"Token invalido! {$e->getMessage()}"
				);

			} catch (BeforeValidException $e) {
				// provided JWT is trying to be used before "nbf" claim OR
				// provided JWT is trying to be used before "iat" claim.
				return array(
					'statusCode'=>403,
					'description'=>"Token invalido! "
				);
			} catch (ExpiredException $e) {
				// provided JWT is trying to be used after "exp" claim.
				return array(
					'statusCode'=>403,
					'description'=>"Token ya expiro! "
				);
			} catch (UnexpectedValueException $e) {
				// provided JWT is malformed OR
				// provided JWT is missing an algorithm / using an unsupported algorithm OR
				// provided JWT algorithm does not match provided key OR
				// provided key ID in key/key-array is empty or invalid.
			}

      	}else {

			return null;

     	}
	}
}
