<?php

declare(strict_types=1);

namespace Src\Middlewares\Auth;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;
use Src\Services\Auth\AuthService;
use Src\Services\EmailService;

class AuthMiddleware implements MiddlewareInterface
{
	private AuthService $authService;
	private ResponseFactory $responseFactory;

	public function __construct(AuthService $authService)
	{
		$this->authService = $authService;
		$this->responseFactory = new ResponseFactory();
	}

	public function process(Request $request, RequestHandler $handler): Response
	{
		$header = $request->getHeaderLine('Authorization');
		$jwt = sscanf($header, 'Bearer %s')[0] ?? '';

		if (!$jwt) {
			$response = $this->responseFactory->createResponse();
			$response->getBody()->write(json_encode([
				'status' => 'error',
				'message' => 'Token no proporcionado',
				'code' => 401
			]));
			return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
		}

		try {
			$decoded = $this->authService->validateToken($jwt);
			$request = $request->withAttribute('user', $decoded);
			return $handler->handle($request);
		} catch (\Exception $e) {
			$response = $this->responseFactory->createResponse();
			$response->getBody()->write(json_encode([
				'status' => 'error',
				'message' => $e->getMessage(),
				'code' => 401
			]));
			return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
		}
	}
}
