# Sistema de Monitoreo y Logging

Este proyecto tiene implementado un sistema completo de monitoreo de errores que registra automáticamente todos los problemas sin esperar a que los usuarios los reporten.

## 📊 Características Actuales

### 1. **Logging Automático con Monolog**
- ✅ Todos los errores 5xx se registran automáticamente
- ✅ Archivos rotativos diarios (mantiene 30 días de historia)
- ✅ Contexto completo: Request, headers, IP, user-agent, stack trace
- ✅ Sanitización de datos sensibles (passwords, tokens, etc.)

### 2. **Archivos de Log**

Los logs se guardan en `/logs/` con rotación automática:

```
logs/
├── errors.log          # Errores críticos (500+)
├── warnings.log        # Advertencias (400+, validaciones)
├── info.log           # Eventos informativos (opcional)
└── errors-YYYY-MM-DD.log  # Archivos históricos rotativos
```

### 3. **Información Registrada**

Cada error incluye:
- **Exception**: Tipo, mensaje, código
- **Ubicación**: Archivo y línea donde ocurrió
- **Stack trace**: Completo para debugging
- **Request**: Método, URI, headers, body (sanitizado)
- **Cliente**: IP real, User-Agent
- **Timestamp**: Fecha y hora exacta

## 🚀 Uso del LoggerService

### En cualquier parte del código:

```php
use Src\Services\LoggerService;

class MiController extends BaseController
{
    private LoggerService $logger;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->logger = $container->get(LoggerService::class);
    }

    public function miMetodo($request, $response)
    {
        try {
            // Tu código...
            
            // Registrar eventos importantes
            $this->logger->logInfo('Usuario creó un proyecto', [
                'user_id' => $userId,
                'project_id' => $projectId
            ]);
            
        } catch (\Exception $e) {
            // Los errores 5xx ya se registran automáticamente
            // Pero puedes agregar contexto adicional:
            $this->logger->logError($e, $request, [
                'action' => 'create_project',
                'user_id' => $userId
            ]);
            
            throw $e;
        }
    }
}
```

### Registrar eventos de seguridad:

```php
// Intento de login fallido
$this->logger->logSecurityEvent('Intento de login fallido', [
    'email' => $email,
    'ip' => $request->getServerParams()['REMOTE_ADDR']
]);

// Acceso no autorizado
$this->logger->logSecurityEvent('Intento de acceso sin permisos', [
    'user_id' => $userId,
    'resource' => $resourceId
]);
```

## 📈 Middleware de Logging (Opcional)

Si quieres registrar TODAS las peticiones HTTP:

```php
// En core/middleware.php
use Src\Middlewares\Logging\RequestLoggingMiddleware;

return function (App $app) {
    // ... otros middlewares
    
    // Registrar todas las peticiones (útil para debugging)
    $container = $app->getContainer();
    $app->add(new RequestLoggingMiddleware(
        $container->get(LoggerService::class),
        false // false = solo registra errores, true = registra todo
    ));
};
```

## 🔔 Integración con Sentry (Recomendado para Producción)

Para recibir **alertas en tiempo real** por email/Slack cuando ocurran errores:

### 1. Instalar Sentry SDK:

```bash
docker-compose exec -u root app composer require sentry/sentry
```

### 2. Crear cuenta gratuita en [sentry.io](https://sentry.io)

### 3. Agregar a `.env`:

```env
SENTRY_DSN=https://tu-dsn@sentry.io/123456
SENTRY_ENVIRONMENT=production
```

### 4. Configurar en `core/dependencies.php`:

```php
// Sentry Logger Handler
if (!empty($_ENV['SENTRY_DSN'])) {
    \Sentry\init([
        'dsn' => $_ENV['SENTRY_DSN'],
        'environment' => $_ENV['SENTRY_ENVIRONMENT'] ?? 'production',
        'traces_sample_rate' => 1.0,
    ]);
    
    // Agregar handler de Sentry a Monolog
    LoggerInterface::class => function (ContainerInterface $c) {
        $logger = new Logger('app');
        
        // Handler de Sentry (envía a la nube)
        $sentryHandler = new \Sentry\Monolog\Handler(
            new \Sentry\State\Hub(),
            Logger::ERROR
        );
        $logger->pushHandler($sentryHandler);
        
        // Handlers locales (archivos)
        // ... resto del código
    },
}
```

## 📱 Beneficios de Sentry

- ✅ **Alertas en tiempo real** por email/Slack/Discord
- ✅ **Dashboard web** para ver todos los errores
- ✅ **Agrupación inteligente** de errores similares
- ✅ **Breadcrumbs**: Ve qué hizo el usuario antes del error
- ✅ **Release tracking**: Sabe qué versión causó el error
- ✅ **Performance monitoring**: Detecta endpoints lentos
- ✅ **Gratuito hasta 5,000 eventos/mes**

## 🧪 Ver los Logs

### Logs en tiempo real:

```bash
# Ver errores en tiempo real
docker-compose exec app tail -f /var/www/html/logs/errors.log

# Ver warnings
docker-compose exec app tail -f /var/www/html/logs/warnings.log

# Ver solo errores de hoy
docker-compose exec app cat /var/www/html/logs/errors-$(date +%Y-%m-%d).log
```

### Buscar errores específicos:

```bash
# Buscar errores relacionados con MySQL
docker-compose exec app grep -i mysql /var/www/html/logs/errors.log

# Buscar por código de error
docker-compose exec app grep "statusCode.*500" /var/www/html/logs/errors.log

# Contar errores del día
docker-compose exec app grep -c "ERROR" /var/www/html/logs/errors-$(date +%Y-%m-%d).log
```

## 🛡️ Datos Sanitizados

El sistema automáticamente **NO registra**:
- Contraseñas (`password`, `password_usuario`)
- Tokens (`token`, `api_key`, `secret`)
- Headers sensibles (`Authorization`, `Cookie`)
- Datos de tarjetas de crédito (`credit_card`, `cvv`)

Estos campos se reemplazan con `***REDACTED***` en los logs.

## 📊 Análisis de Logs

Para análisis avanzado, puedes usar herramientas como:

1. **ELK Stack** (Elasticsearch + Logstash + Kibana)
2. **Graylog** (open source)
3. **Datadog** (pago, muy completo)
4. **Sentry** (recomendado, fácil de configurar)

## ⚠️ Importante

- Los logs se rotan automáticamente cada 30 días
- NO commitear archivos `.log` al repositorio (ya está en .gitignore)
- En producción, considera usar Sentry para alertas inmediatas
- Revisa los logs regularmente para detectar patrones

## 🔧 Configuración Avanzada

Ver `src/Services/LoggerService.php` para personalizar:
- Campos a sanitizar
- Formato de los logs
- Contexto adicional a capturar
- Niveles de logging (DEBUG, INFO, WARNING, ERROR)
