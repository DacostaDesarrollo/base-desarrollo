<?php

declare(strict_types=1);

//use Src\Middlewares\Auth\AuthMiddleware;
use Slim\App;
use Slim\Middleware\ContentLengthMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

return function (App $app) {

    //$app->add(AuthMiddleware::class);

    // Content Length Middleware
    $app->add(new ContentLengthMiddleware());
    
    // JSON Parsing Middleware
    $app->add(function (Request $request, RequestHandler $handler) {
        $contentType = $request->getHeaderLine('Content-Type');
        if (strstr($contentType, 'application/json')) {
            $contents = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request = $request->withParsedBody($contents);
            }
        }
        return $handler->handle($request);
    });

    // CORS Middleware
    $app->add(function (Request $request, RequestHandler $handler) {
        $response = $handler->handle($request);
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    });

    // Logging Middleware
    /* $app->add(function (Request $request, RequestHandler $handler) {
        $route = $request->getUri()->getPath();
        $method = $request->getMethod();
        
        // Log inicio de la petición
        error_log(sprintf('[%s] Iniciando petición %s %s', 
            date('Y-m-d H:i:s'), 
            $method, 
            $route
        ));

        $response = $handler->handle($request);

        // Log fin de la petición
        error_log(sprintf('[%s] Finalizando petición %s %s - Status: %s', 
            date('Y-m-d H:i:s'), 
            $method, 
            $route,
            $response->getStatusCode()
        ));

        return $response;
    }); */

    // Security Headers Middleware
    $app->add(function (Request $request, RequestHandler $handler) {
        $response = $handler->handle($request);
        return $response
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-XSS-Protection', '1; mode=block')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    });

    // Performance Tracking Middleware
    $app->add(function (Request $request, RequestHandler $handler) {
        $startTime = microtime(true);
        
        $response = $handler->handle($request);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // en millisegundos

        return $response->withHeader('X-Execution-Time', sprintf('%.2f ms', $executionTime));
    });

    // Session Middleware
    $app->add(function ($request, $handler) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return $handler->handle($request);
    });

    // Timestamp Middleware
    $app->add(function ($request, $handler) {
        $request = $request->withAttribute('timestamp', time());
        return $handler->handle($request);
    });
};
