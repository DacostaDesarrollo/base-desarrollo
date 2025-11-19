<?php

declare(strict_types=1);

namespace Src\Middlewares\Logging;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Src\Services\LoggerService;

/**
 * Middleware para registrar todas las peticiones HTTP
 * 
 * Útil para debugging y auditoría de accesos
 */
class RequestLoggingMiddleware implements MiddlewareInterface
{
    private LoggerService $logger;
    private bool $logSuccessfulRequests;

    /**
     * @param LoggerService $logger
     * @param bool $logSuccessfulRequests Si es false, solo registra errores 4xx y 5xx
     */
    public function __construct(LoggerService $logger, bool $logSuccessfulRequests = true)
    {
        $this->logger = $logger;
        $this->logSuccessfulRequests = $logSuccessfulRequests;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $startTime = microtime(true);
        $method = $request->getMethod();
        $uri = (string) $request->getUri();
        
        // Procesar el request
        $response = $handler->handle($request);
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // en ms
        $statusCode = $response->getStatusCode();

        // Decidir si registrar según el código de status
        $shouldLog = $this->logSuccessfulRequests || $statusCode >= 400;

        if ($shouldLog) {
            $context = [
                'method' => $method,
                'uri' => $uri,
                'status' => $statusCode,
                'duration_ms' => $duration,
                'ip' => $this->getClientIp($request),
                'user_agent' => $request->getHeaderLine('User-Agent'),
            ];

            if ($statusCode >= 500) {
                $this->logger->logError(
                    new \Exception("HTTP {$statusCode} - {$method} {$uri}"),
                    $request,
                    $context
                );
            } elseif ($statusCode >= 400) {
                $this->logger->logWarning(
                    "HTTP {$statusCode} - {$method} {$uri}",
                    $context
                );
            } else {
                $this->logger->logInfo(
                    "{$method} {$uri} - {$statusCode}",
                    $context
                );
            }
        }

        return $response;
    }

    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        
        if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $ipList = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            return trim($ipList[0]);
        }
        
        if (!empty($serverParams['HTTP_X_REAL_IP'])) {
            return $serverParams['HTTP_X_REAL_IP'];
        }
        
        return $serverParams['REMOTE_ADDR'] ?? 'unknown';
    }
}
