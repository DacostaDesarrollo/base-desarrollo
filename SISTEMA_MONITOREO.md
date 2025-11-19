# 🛡️ Sistema de Monitoreo de Errores - Configurado

## ✅ ¿Qué se instaló?

### 1. **LoggerService** - Servicio centralizado de logging
**Ubicación**: `src/Services/LoggerService.php`

**Características**:
- ✅ Sanitiza automáticamente datos sensibles (passwords, tokens)
- ✅ Captura contexto completo del request (IP, headers, body)
- ✅ Métodos convenientes: `logError()`, `logWarning()`, `logInfo()`, `logSecurityEvent()`
- ✅ Incluye stack traces completos para debugging

### 2. **HttpErrorHandler mejorado** - Captura automática de errores
**Ubicación**: `src/handlers/HttpErrorHandler.php`

**Características**:
- ✅ Registra AUTOMÁTICAMENTE todos los errores 5xx
- ✅ No necesitas hacer nada, funciona transparentemente
- ✅ Incluye el request completo en el log

### 3. **Configuración de Monolog** - Logger profesional
**Ubicación**: `core/dependencies.php`

**Características**:
- ✅ Archivos rotativos diarios (30 días de historia)
- ✅ 3 archivos separados: errors.log, warnings.log, info.log
- ✅ Formato estructurado con timestamps y UIDs únicos

### 4. **RequestLoggingMiddleware** (Opcional)
**Ubicación**: `src/Middlewares/Logging/RequestLoggingMiddleware.php`

**Características**:
- ⚠️ Desactivado por defecto (puedes activarlo si quieres)
- ✅ Registra TODAS las peticiones HTTP si lo activas
- ✅ Incluye tiempo de ejecución de cada request

### 5. **Documentación completa**
**Ubicación**: `docs/LOGGING.md`

**Incluye**:
- ✅ Guía de uso completa
- ✅ Ejemplos de código
- ✅ Instrucciones para ver logs
- ✅ Configuración opcional de Sentry

## 🚀 ¿Cómo funciona?

### Automático (No requiere cambios en tu código)

```php
// Si ocurre un error 500 en CUALQUIER parte de tu aplicación:
throw new Exception('Error de conexión a BD');

// Se registra automáticamente en logs/errors.log con:
// - Mensaje del error
// - Archivo:línea donde ocurrió
// - Stack trace completo
// - Request: método, URI, headers, body
// - IP del cliente
// - User-Agent
// - Timestamp
```

### Manual (Para eventos importantes)

```php
use Src\Services\LoggerService;

class MiController extends BaseController {
    private LoggerService $logger;
    
    public function __construct(ContainerInterface $container) {
        parent::__construct($container);
        $this->logger = $container->get(LoggerService::class);
    }
    
    public function crearProyecto($request, $response) {
        // Registrar evento importante
        $this->logger->logInfo('Proyecto creado', [
            'user_id' => $userId,
            'project_id' => $projectId
        ]);
        
        // Registrar evento de seguridad
        $this->logger->logSecurityEvent('Intento de login fallido', [
            'email' => $email,
            'ip' => $clientIp
        ]);
    }
}
```

## 📊 Ver los logs

### En tiempo real:
```bash
# Errores en vivo
docker-compose exec app tail -f /var/www/html/logs/errors.log

# Advertencias en vivo
docker-compose exec app tail -f /var/www/html/logs/warnings.log
```

### Buscar errores:
```bash
# Buscar por palabra clave
docker-compose exec app grep -i "mysql" /var/www/html/logs/errors.log

# Contar errores del día
docker-compose exec app grep -c "ERROR" /var/www/html/logs/errors-$(date +%Y-%m-%d).log

# Ver últimos 50 errores
docker-compose exec app tail -n 50 /var/www/html/logs/errors.log
```

## 🔔 Siguientes pasos (Opcional)

### Para monitoreo en tiempo real con alertas:

1. **Instalar Sentry** (Recomendado):
```bash
docker-compose exec -u root app composer require sentry/sentry
```

2. **Crear cuenta gratuita**: [sentry.io](https://sentry.io)

3. **Agregar a `.env`**:
```env
SENTRY_DSN=tu-dsn-de-sentry
SENTRY_ENVIRONMENT=production
```

4. **Beneficios**:
- ✅ Alertas por email/Slack cuando hay errores
- ✅ Dashboard web profesional
- ✅ Agrupación inteligente de errores
- ✅ Notificaciones instantáneas
- ✅ GRATIS hasta 5,000 eventos/mes

## 📝 Archivos creados/modificados

```
✅ src/Services/LoggerService.php                    [NUEVO]
✅ src/handlers/HttpErrorHandler.php                 [MODIFICADO]
✅ src/Middlewares/Logging/RequestLoggingMiddleware.php [NUEVO]
✅ core/dependencies.php                             [MODIFICADO]
✅ public/index.php                                  [MODIFICADO]
✅ logs/                                             [NUEVO]
✅ logs/.gitignore                                   [NUEVO]
✅ docs/LOGGING.md                                   [NUEVO]
✅ docs/examples/LoggingExampleController.php        [NUEVO]
```

## ⚠️ Importante

- ✅ **Los errores se registran automáticamente** - No necesitas modificar código existente
- ✅ **Datos sensibles protegidos** - Passwords, tokens, etc. se ocultan automáticamente
- ✅ **Logs rotativos** - Se mantienen 30 días de historia automáticamente
- ✅ **Listo para producción** - Funciona inmediatamente sin configuración adicional

## 🎯 Resumen

**Antes**: Los errores ocurrían y nadie se enteraba hasta que los usuarios reportaban.

**Ahora**: 
- ✅ Todos los errores se registran automáticamente con contexto completo
- ✅ Puedes revisar los logs diariamente para encontrar problemas
- ✅ Detectas patrones de errores antes de que afecten a muchos usuarios
- ✅ Tienes trazabilidad completa para debugging

**Con Sentry (opcional)**: Recibes notificaciones instantáneas cuando algo falla.
