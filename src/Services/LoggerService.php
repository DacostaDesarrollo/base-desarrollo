<?php

declare(strict_types=1);

namespace Src\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\JsonFormatter;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Servicio de Logging centralizado
 * 
 * Registra errores, warnings y eventos importantes del sistema
 * con contexto completo para facilitar el debugging
 */
class LoggerService
{
    private Logger $logger;
    private array $settings;

    public function __construct(Logger $logger, array $settings)
    {
        $this->logger = $logger;
        $this->settings = $settings;
    }

    /**
     * Registra un error con contexto completo
     * 
     * @param \Throwable $exception La excepción capturada
     * @param ServerRequestInterface|null $request Request HTTP (opcional)
     * @param array $additionalContext Contexto adicional
     */
    public function logError(
        \Throwable $exception,
        ?ServerRequestInterface $request = null,
        array $additionalContext = []
    ): void {
        $context = $this->buildErrorContext($exception, $request, $additionalContext);
        
        $this->logger->error($exception->getMessage(), $context);
    }

    /**
     * Registra un warning (advertencia)
     */
    public function logWarning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * Registra información general (eventos importantes)
     */
    public function logInfo(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * Registra intentos de seguridad sospechosos
     */
    public function logSecurityEvent(string $message, array $context = []): void
    {
        $context['security_event'] = true;
        $this->logger->warning("[SECURITY] {$message}", $context);
    }

    /**
     * Construye el contexto completo del error
     */
    private function buildErrorContext(
        \Throwable $exception,
        ?ServerRequestInterface $request,
        array $additionalContext
    ): array {
        $context = [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Agregar información del request si está disponible
        if ($request !== null) {
            $context['request'] = [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'headers' => $this->sanitizeHeaders($request->getHeaders()),
                'ip' => $this->getClientIp($request),
                'user_agent' => $request->getHeaderLine('User-Agent'),
            ];

            // Agregar body solo si no es muy grande
            $body = (string) $request->getBody();
            if (strlen($body) < 5000) {
                $parsedBody = $request->getParsedBody();
                $context['request']['body'] = $this->sanitizeSensitiveData($parsedBody ?? []);
            }
        }

        return array_merge($context, $additionalContext);
    }

    /**
     * Sanitiza headers removiendo información sensible
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['***REDACTED***'];
            }
        }

        return $headers;
    }

    /**
     * Sanitiza datos sensibles (passwords, tokens, etc)
     */
    private function sanitizeSensitiveData($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $sensitiveFields = [
            'password',
            'password_usuario',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'cvv'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***REDACTED***';
            }
        }

        return $data;
    }

    /**
     * Obtiene la IP real del cliente
     */
    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        
        // Intenta obtener IP real detrás de proxies
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
