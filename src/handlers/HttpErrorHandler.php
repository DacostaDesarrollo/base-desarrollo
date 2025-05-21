<?php

declare(strict_types=1);

namespace Src\handlers;

use Src\Actions\ActionError;
use Src\Actions\ActionPayload;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpNotImplementedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Handlers\ErrorHandler;
use Throwable;
use Slim\Interfaces\CallableResolverInterface;
use Slim\Psr7\Factory\ResponseFactory;

class HttpErrorHandler extends ErrorHandler
{
    public function __construct(
        CallableResolverInterface $callableResolver,
        ResponseFactory $responseFactory
    ) {
        parent::__construct($callableResolver, $responseFactory);
    }

    /**
     * @inheritdoc
     */
    protected function respond(): Response
    {
        $exception = $this->exception;
        $statusCode = 500;
        $description = $exception->getMessage();
        $type = get_class($exception);
        $trace = $exception->getTraceAsString();

        if ($exception instanceof HttpNotFoundException) {
            $statusCode = 404;
            $description = 'Not found.';
        } elseif ($exception instanceof HttpMethodNotAllowedException) {
            $statusCode = 405;
            $description = 'Method not allowed.';
        } elseif ($exception instanceof HttpBadRequestException) {
            $statusCode = 400;
            $description = 'Bad request.';
        } elseif ($exception instanceof HttpUnauthorizedException) {
            $statusCode = 401;
            $description = 'Unauthorized.';
        } elseif ($exception instanceof HttpForbiddenException) {
            $statusCode = 403;
            $description = 'Forbidden.';
        } elseif ($exception instanceof HttpNotImplementedException) {
            $statusCode = 501;
            $description = 'Not implemented.';
        }

        $error = [
            'statusCode' => $statusCode,
            'error' => [
                'type' => $type,
                'description' => $description,
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $trace
            ],
        ];

        $payload = json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
