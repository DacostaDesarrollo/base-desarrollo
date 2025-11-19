<?php
namespace Src\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use Src\Services\Auth\AuthService;

class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->authService = $container->get(AuthService::class);
    }

    public function login(Request $request, Response $response, array $args): Response
    {
        try {
            $data = $request->getParsedBody();
            
            $result = $this->authService->authenticateUser(
                $data['email_usuario'] ?? '',
                $data['password_usuario'] ?? ''
            );

            return $this->respondWithData($response, [
                'usuario' => $result['user'],
                'token' => $result['token']
            ]);

        } catch (\Exception $e) {
            return $this->respondWithError($response, $e->getMessage(), $e->getCode() ?: 404);
        }
    }

    public function register(Request $request, Response $response, array $args): Response
    {
        try {
            $data = $request->getParsedBody();
            
            $result = $this->authService->registerUser($data);

            return $this->respondWithData($response, [
                'usuario' => $result,
            ]);

        } catch (\Exception $e) {
            return $this->respondWithError($response, $e->getMessage(), $e->getCode() ?: 404);
        }
    }
}