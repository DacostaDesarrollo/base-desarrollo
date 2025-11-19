<?php

/**
 * Ejemplo de cómo usar el LoggerService en tus controladores
 * 
 * Este archivo muestra los diferentes casos de uso del sistema de logging
 */

namespace Src\Controllers\Examples;

use Src\Controllers\BaseController;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Services\LoggerService;

class LoggingExampleController extends BaseController
{
    private LoggerService $logger;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->logger = $container->get(LoggerService::class);
    }

    /**
     * Ejemplo 1: Registrar eventos informativos
     * Útil para auditoría y seguimiento de acciones importantes
     */
    public function createResource(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Simular creación de recurso
            $resourceId = 123;
            $userId = 456;
            
            // Registrar el evento exitoso
            $this->logger->logInfo('Nuevo recurso creado', [
                'resource_id' => $resourceId,
                'user_id' => $userId,
                'type' => $data['type'] ?? 'unknown'
            ]);
            
            return $this->respondWithData($response, [
                'id' => $resourceId,
                'message' => 'Recurso creado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            // Los errores 5xx se registran automáticamente
            // Pero podemos agregar contexto adicional
            $this->logger->logError($e, $request, [
                'action' => 'create_resource',
                'user_id' => $userId ?? null
            ]);
            
            return $this->respondWithError($response, $e->getMessage(), 500);
        }
    }

    /**
     * Ejemplo 2: Registrar intentos sospechosos de seguridad
     * Útil para detectar ataques o comportamiento anormal
     */
    public function loginAttempt(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $email = $data['email'] ?? '';
        
        // Simular validación de credenciales
        $isValid = false;
        
        if (!$isValid) {
            // Registrar intento fallido de login (posible ataque)
            $this->logger->logSecurityEvent('Intento de login fallido', [
                'email' => $email,
                'ip' => $this->getClientIp($request),
                'user_agent' => $request->getHeaderLine('User-Agent'),
                'attempts_count' => 3 // Podrías llevar un contador
            ]);
            
            return $this->respondWithError($response, 'Credenciales inválidas', 401);
        }
        
        return $this->respondWithData($response, ['token' => 'example-token']);
    }

    /**
     * Ejemplo 3: Registrar warnings para situaciones inusuales
     * Útil para detectar problemas antes de que se vuelvan críticos
     */
    public function processPayment(Request $request, Response $response): Response
    {
        $amount = $request->getParsedBody()['amount'] ?? 0;
        
        // Detectar monto sospechosamente alto
        if ($amount > 10000) {
            $this->logger->logWarning('Pago con monto inusualmente alto detectado', [
                'amount' => $amount,
                'user_id' => 123,
                'ip' => $this->getClientIp($request)
            ]);
        }
        
        // Procesar pago...
        
        return $this->respondWithData($response, ['status' => 'processed']);
    }

    /**
     * Ejemplo 4: Errores automáticamente registrados
     * NO necesitas hacer nada, el ErrorHandler lo registra automáticamente
     */
    public function automaticErrorLogging(Request $request, Response $response): Response
    {
        // Este error será registrado automáticamente con todo el contexto
        throw new \Exception('Este error se registrará automáticamente en logs/errors.log');
        
        // El log incluirá:
        // - Mensaje del error
        // - Archivo y línea donde ocurrió
        // - Stack trace completo
        // - Datos del request (método, URI, headers, body)
        // - IP del cliente
        // - User-Agent
        // - Timestamp
    }

    /**
     * Ejemplo 5: Registrar operaciones costosas o lentas
     * Útil para monitorear performance
     */
    public function expensiveOperation(Request $request, Response $response): Response
    {
        $startTime = microtime(true);
        
        // Operación costosa (ej: generar reporte)
        sleep(2); // Simular operación lenta
        
        $duration = (microtime(true) - $startTime) * 1000; // en ms
        
        // Advertir si tardó mucho
        if ($duration > 1000) {
            $this->logger->logWarning('Operación lenta detectada', [
                'operation' => 'generate_report',
                'duration_ms' => round($duration, 2),
                'threshold_ms' => 1000
            ]);
        }
        
        return $this->respondWithData($response, ['status' => 'completed']);
    }

    /**
     * Ejemplo 6: Registrar cambios críticos
     * Útil para auditoría de compliance
     */
    public function updateCriticalData(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Antes del cambio
        $oldValue = 'valor_anterior';
        $newValue = $data['value'];
        
        // Registrar el cambio para auditoría
        $this->logger->logInfo('Datos críticos modificados', [
            'field' => 'critical_field',
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'user_id' => 123,
            'ip' => $this->getClientIp($request),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        return $this->respondWithData($response, ['status' => 'updated']);
    }

    /**
     * Helper para obtener IP real del cliente
     */
    private function getClientIp(Request $request): string
    {
        $serverParams = $request->getServerParams();
        
        if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
            $ipList = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
            return trim($ipList[0]);
        }
        
        return $serverParams['REMOTE_ADDR'] ?? 'unknown';
    }
}
