<?php

declare(strict_types=1);

namespace Src\Middlewares\Performance;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;

class ProfilingMiddleware implements MiddlewareInterface
{
    private array $timings = [];
    private float $startTime;

    public function process(Request $request, RequestHandler $handler): Response
    {
        $this->startTime = microtime(true);
        
        // Registrar el inicio de la petición
        $this->timings['request_start'] = [
            'time' => microtime(true),
            'memory' => memory_get_usage(true)
        ];

        // Registrar la ruta y método
        $this->timings['route'] = [
            'path' => $request->getUri()->getPath(),
            'method' => $request->getMethod()
        ];

        // Registrar el controlador y método si están disponibles
        $route = $request->getAttribute('route');
        if ($route) {
            $handler = $route->getCallable();
            if (is_array($handler)) {
                $this->timings['handler'] = [
                    'controller' => get_class($handler[0]),
                    'method' => $handler[1]
                ];
            }
        }

        // Registrar el tiempo de ejecución de la petición
        $response = $handler->handle($request);
        
        // Registrar el final de la petición
        $this->timings['request_end'] = [
            'time' => microtime(true),
            'memory' => memory_get_usage(true)
        ];

        // Calcular estadísticas
        $this->timings['statistics'] = [
            'total_time' => microtime(true) - $this->startTime,
            'memory_peak' => memory_get_peak_usage(true),
            'memory_usage' => memory_get_usage(true)
        ];

        // Añadir los headers de profiling a la respuesta
        return $response
            ->withHeader('X-Profile-Total-Time', sprintf('%.4f', $this->timings['statistics']['total_time']))
            ->withHeader('X-Profile-Memory-Peak', sprintf('%.2f MB', $this->timings['statistics']['memory_peak'] / 1024 / 1024))
            ->withHeader('X-Profile-Memory-Usage', sprintf('%.2f MB', $this->timings['statistics']['memory_usage'] / 1024 / 1024));
    }

    public function getTimings(): array
    {
        return $this->timings;
    }
} 