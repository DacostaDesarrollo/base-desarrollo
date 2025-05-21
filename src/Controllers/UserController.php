<?php
namespace Src\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Illuminate\Database\Capsule\Manager as DB;

use Src\Models\Usuario as Usuario;

class UserController extends BaseController {
    
    public function getUser(Request $request, Response $response): Response {
        $email = $this->container["token"]->decoded['sub'];
        $user = $this->db->table('usuarios')->where('email', $email)->first();
        
        if (!$user) {
            return $this->respondWithError($response, 'Usuario no encontrado', 404);
        }

        return $this->respondWithData($response, $user);
    }

    public function listUsers(Request $request, Response $response): Response {
        $params = $request->getQueryParams();
        $perPage = isset($params['perPage']) ? $params['perPage'] : 15;
        $page = isset($params['page']) ? $params['page'] : 1;
        $offset = ($page - 1) * $perPage;
        
        $users = Usuario::query();
        $users->where('estado_usuario', '=', 1);

        if (!empty($params['query']) && $params['query'] != "null") {
            $users->where(function ($query) use ($params) {
                $query->where('nombre_usuario', 'LIKE', '%' . $params['query'] . '%')
                      ->orWhere('apellido_usuario', 'LIKE', '%' . $params['query'] . '%')
                      ->orWhere('identificacion_tributaria_usuario', 'LIKE', '%' . $params['query'] . '%')
                      ->orWhere('email_usuario', 'LIKE', '%' . $params['query'] . '%');
            });
        }

        if (!empty($params['country']) && $params['country'] != "null") {
            $users->where('pais_usuario', '=', $params['country']);
        }

        if (!empty($params['role']) && $params['role'] != "null") {
            $users->where('tipo_usuario_fk', '=', $params['role']);
        }

        if (!empty($params['status']) && $params['status'] != "null") {
            $users->where('suscripciones.estado_pago_suscripcion', '=', $params['status']);
        }
    
        $users->join('tipo_usuarios', 'usuarios.tipo_usuario_fk', '=', 'tipo_usuarios.slug_tipo')
              ->leftJoin('suscripciones', 'usuarios.id_usuario', '=', 'suscripciones.id_usuario_suscripcion')
              ->select('usuarios.*', 'tipo_usuarios.*', 'suscripciones.*')
              ->offset($offset)
              ->limit($perPage)
              ->orderBy('id_usuario', 'desc');

        $usersData = $users->get();
        
        if (empty($usersData->toArray())) {
            return $this->respondWithError($response, 'No se encontraron usuarios', 404);
        }

        return $this->respondWithData($response, $usersData);
    }

    public function getUserId(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        if (!$id) {
            return $this->respondWithError($response, "ID de usuario requerido");
        }

        $user = Usuario::where('id_usuario', $id)->first();
        
        if (empty($user)) {
            return $this->respondWithError($response, 'El usuario no existe', 400);
        }

        return $this->respondWithData($response, ['user' => $user]);
    }
   
    /**
     * Actualizar datos de usuarios tipo miembros
     */
    public function updateUser(Request $request, Response $response, array $args): Response {
        $data = $request->getParsedBody();
        $idUser = $args['idUser'];
        
        unset($data['categoria_jurado']);

        if (!empty($data['password_usuario'])) {
            $data['password_usuario'] = md5($data['password_usuario']);
        } else {
            unset($data['password_usuario']);
        }
        
        $updateQuery = Usuario::where('id_usuario', $idUser)->update($data);

        if (!$updateQuery) {
            return $this->respondWithError($response, 'El usuario no se actualizó', 400);
        }
        
        return $this->respondWithData($response, [
            'message' => 'Usuario actualizado con éxito'
        ]);
    }

    public function deleteUser(Request $request, Response $response, array $args): Response {
        $idUser = $args['idUser'];
        $user = Usuario::where('id_usuario', $idUser)->first();
        
        if (!$user) {
            return $this->respondWithError($response, 'Usuario no encontrado', 404);
        }

        $user->delete();
        
        return $this->respondWithData($response, [
            'message' => '¡El usuario se eliminó con éxito!'
        ]);
    }
}