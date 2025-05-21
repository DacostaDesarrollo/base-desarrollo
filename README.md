# API REST con Slim Framework

Este proyecto es una API REST desarrollada con Slim Framework 4, que proporciona endpoints para la gestión de usuarios, autenticación, pagos, suscripciones y archivos.

## Requisitos

- PHP 8.0 o superior
- Composer
- MySQL/MariaDB
- Docker (opcional)

## Instalación

1. Clonar el repositorio:
```bash
git clone [url-del-repositorio]
cd [nombre-del-directorio]
```

2. Instalar dependencias:
```bash
composer install
```

3. Configurar el entorno:
- Copiar `.env.example` a `.env`
- Ajustar las variables de entorno según sea necesario

4. Configurar la base de datos:
- Crear una base de datos MySQL
- Ejecutar las migraciones:
```bash
php migrations/migrate.php
```

## Estructura del Proyecto

```
├── core/
│   ├── dependencies.php    # Configuración de dependencias
│   ├── middleware.php      # Configuración de middlewares
│   ├── settings.php        # Configuración general
│   └── bootstrap.php       # Inicialización de la aplicación
├── migrations/            # Migraciones de la base de datos
├── public/               # Punto de entrada público
├── src/
│   ├── Controllers/      # Controladores
│   ├── Middlewares/      # Middlewares
│   ├── Models/          # Modelos
│   ├── Services/        # Servicios
│   └── routes.php       # Definición de rutas
└── uploads/             # Directorio para archivos subidos
```

## Endpoints Disponibles

### Autenticación
- `POST /auth/login` - Iniciar sesión
- `POST /auth/register` - Registrar nuevo usuario
- `POST /auth/forgot-password` - Solicitar recuperación de contraseña
- `POST /auth/reset-password` - Restablecer contraseña

### Usuarios
- `GET /users` - Listar usuarios (requiere autenticación y rol admin/jurado)
- `GET /users/{id}` - Obtener usuario específico
- `PUT /users/{idUser}` - Actualizar usuario
- `DELETE /users/{idUser}` - Eliminar usuario

### Pagos (PayU)
- `POST /payments/method/payu/create` - Crear pago
- `POST /payments/method/payu/responseUrl` - URL de respuesta
- `POST /payments/method/payu/confirmationUrl` - URL de confirmación
- `GET /payments/method/payu/status/{id}` - Estado del pago
- `GET /payments/method/payu/list` - Listar pagos
- `GET /payments/method/payu/list/{userId}` - Listar pagos por usuario

### Suscripciones
- `GET /suscriptions` - Listar suscripciones
- `GET /suscriptions/{id}` - Obtener suscripción específica
- `POST /suscriptions` - Crear suscripción
- `PUT /suscriptions/{id}` - Actualizar suscripción
- `DELETE /suscriptions/{id}` - Eliminar suscripción

### Archivos
- `POST /files` - Subir archivo
- `GET /files/group` - Obtener grupo de archivos
- `GET /files/assets/{path}/{filename}` - Obtener archivo
- `DELETE /files/{id}` - Eliminar archivo

### Países
- `GET /paises` - Listar países

## Middlewares

### AuthMiddleware
- Verifica la autenticación del usuario mediante JWT
- Se aplica a rutas que requieren autenticación

### RoleMiddleware
- Verifica los roles del usuario
- Se aplica a rutas que requieren roles específicos

### UploadedFilesMiddleware
- Maneja la subida de archivos
- Configura el directorio de uploads

### FilesValidMiddleware
- Valida los archivos subidos
- Verifica tipos y tamaños permitidos

## Configuración

### Variables de Entorno
```env
DB_HOST=localhost
DB_NAME=database_name
DB_USER=user
DB_PASS=password
JWT_SECRET=your_jwt_secret
```

### Configuración de Uploads
```php
'uploads' => [
    'path' => __DIR__ . '/../uploads',
]
```

## Desarrollo

### Ejecutar en Desarrollo
```bash
php -S localhost:8000 -t public
```

### Ejecutar Tests
```bash
composer test
```

## Docker

### Construir y Ejecutar
```bash
docker-compose up -d
```

### Detener Contenedores
```bash
docker-compose down
```

## Contribución

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE.md](LICENSE.md) para más detalles.
