<?php

namespace Src\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;

abstract class BaseController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Respuesta exitosa con datos
     */
    protected function respondWithData(Response $response, $data = [], int $statusCode = 200): Response
    {
        $payload = [
            'status' => 'success',
            'data' => $data
        ];

        $response->getBody()->write(json_encode($payload));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    /**
     * Respuesta de error
     */
    protected function respondWithError(Response $response, string $message, int $statusCode = 400): Response
    {
        $payload = [
            'status' => 'error',
            'message' => $message
        ];

        $response->getBody()->write(json_encode($payload));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    /**
     * Validación básica de datos
     */
    protected function validateRequired(array $data, array $required): ?string
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return "El campo {$field} es requerido";
            }
        }
        return null;
    }

    /**
     * Obtener datos del usuario autenticado
     */
    protected function getAuthUser(): ?array
    {
        return $this->container->get('user') ?? null;
    }

    /**
     * Respuesta de paginación
     */
    protected function respondWithPagination(
        Response $response, 
        array $data, 
        int $total, 
        int $page, 
        int $perPage
    ): Response {
        $payload = [
            'status' => 'success',
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
} 