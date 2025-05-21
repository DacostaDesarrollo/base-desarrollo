<?php

declare(strict_types=1);

namespace Src\Middlewares\Auth;

use \Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;

class RoleMiddleware implements Middleware
{
    private array $roles = [];
    private array $permissions = [];
    private array $queryPermissions = [];
    private bool $redirect = false;
    private ResponseFactory $responseFactory;

    public function __construct(ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function setPermissions(array $permissions): self
    {
        $this->permissions = $permissions;
        return $this;
    }

    public function setQueryPermissions(array $queryPermissions): self
    {
        $this->queryPermissions = $queryPermissions;
        return $this;
    }

    public function setRedirect(bool $redirect): self
    {
        $this->redirect = $redirect;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $usuario = $request->getAttribute('user');
        if (!$usuario) {
            $response = $this->responseFactory->createResponse();
            $response->getBody()->write(json_encode([
                'error' => 'Usuario no autenticado'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        if (!$this->validate_permissions($usuario)) {
            $response = $this->responseFactory->createResponse();
            $response->getBody()->write(json_encode([
                'error' => 'No tiene permisos para acceder a este recurso'
            ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }

    private function validate_permissions(object $usuario): bool
    {
        if (empty($this->roles)) {
            return true;
        }

        // Verificar si el usuario tiene el campo tipo o rol
        $userRole = $usuario->tipo_usuario_fk;

        if ($userRole === null) {
            return false;
        }

        return in_array($userRole, $this->roles);
    }

    private function validatePermissions(object $user): bool
    {
        if (empty($this->permissions)) {
            return true;
        }

        // Aquí implementa la lógica de validación de permisos según tu sistema
        return true; // Por ahora retornamos true
    }
}
