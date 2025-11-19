<?php
declare(strict_types=1);

namespace Tests\Unit\Services\Auth;

use PHPUnit\Framework\TestCase;
use Src\Services\Auth\AuthService;
use Src\Repositories\Auth\AuthRepositoryInterface;

/**
 * Test Suite para el servicio de autenticación
 * 
 * Estos tests verifican que la lógica de negocio del AuthService
 * funcione correctamente sin depender de la base de datos real
 */
class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private $authRepositoryMock;
    private $emailServiceMock;
    private string $secretKey = 'test-secret-key-for-testing';

    /**
     * setUp() se ejecuta ANTES de cada test
     * 
     * Aquí preparamos los "mocks" (objetos falsos) que simularán
     * el comportamiento del repositorio y el servicio de email
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock del repositorio - simula la base de datos sin conectarse a ella
        $this->authRepositoryMock = $this->createMock(AuthRepositoryInterface::class);
        
        // Mock del email service - simula el envío de emails sin enviarlos realmente
        $this->emailServiceMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['sendEmail'])
            ->getMock();
        
        // Configuramos que siempre retorne éxito (statusCode 200)
        $this->emailServiceMock->method('sendEmail')->willReturn(['statusCode' => 200]);
        
        // Crear instancia del servicio con los mocks inyectados
        $this->authService = new AuthService(
            $this->authRepositoryMock,
            $this->secretKey,
            $this->emailServiceMock
        );
    }

    /**
     * Test: Verifica que un usuario pueda autenticarse con credenciales válidas
     * 
     * Escenario: Un usuario ingresa email/documento y contraseña correctos
     * Resultado esperado: Debe retornar los datos del usuario y un token JWT
     * 
     * @test
     */
    public function it_authenticates_user_with_valid_credentials(): void
    {
        // Arrange (Preparar) - Configuramos los datos de prueba
        $identifier = 'test@example.com';
        $password = 'password123';
        $userData = [
            'id_usuario' => 1,
            'email_usuario' => 'test@example.com',
            'nombre_usuario' => 'Test User',
            'slug_tipo' => 'admin'
        ];

        // Configuramos el mock para que retorne el usuario cuando se busque
        $this->authRepositoryMock
            ->expects($this->once()) // Esperamos que se llame exactamente 1 vez
            ->method('findUserByCredentials')
            ->with($identifier, $password) // Con estos parámetros
            ->willReturn($userData); // Y que retorne estos datos

        // Act (Actuar) - Ejecutamos el método que queremos probar
        $result = $this->authService->authenticateUser($identifier, $password);

        // Assert (Verificar) - Comprobamos que el resultado sea el esperado
        $this->assertIsArray($result); // El resultado debe ser un array
        $this->assertArrayHasKey('user', $result); // Debe tener la key 'user'
        $this->assertArrayHasKey('token', $result); // Debe tener la key 'token'
        $this->assertEquals($userData, $result['user']); // Los datos deben coincidir
        $this->assertNotEmpty($result['token']); // El token no debe estar vacío
    }

    /**
     * Test: Verifica que se lance una excepción cuando las credenciales son inválidas
     * 
     * Escenario: Un usuario ingresa email/contraseña incorrectos
     * Resultado esperado: Debe lanzar una Exception con mensaje y código 404
     * 
     * @test
     */
    public function it_throws_exception_when_credentials_are_invalid(): void
    {
        // Arrange - Configuramos el mock para que NO encuentre usuario
        $this->authRepositoryMock
            ->method('findUserByCredentials')
            ->willReturn(null); // Simula que no se encontró el usuario

        // Assert - Definimos qué excepción esperamos
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Documento, código o contraseña incorrecto');
        $this->expectExceptionCode(404);

        // Act - Ejecutamos y esperamos que lance la excepción
        $this->authService->authenticateUser('invalid@test.com', 'wrong');
    }

    /**
     * Test: Verifica que un nuevo usuario se registre correctamente
     * 
     * Escenario: Se envían datos válidos para crear un nuevo usuario
     * Resultado esperado: Debe retornar los datos del usuario creado con su ID
     * 
     * @test
     */
    public function it_registers_new_user_successfully(): void
    {
        // Arrange - Preparamos los datos del nuevo usuario
        $userData = [
            'email_usuario' => 'new@example.com',
            'identificacion_tributaria_usuario' => '123456789',
            'password_usuario' => 'password123'
        ];

        // Simulamos que el repositorio crea el usuario y le asigna un ID
        $createdUser = array_merge($userData, ['id_usuario' => 1]);

        $this->authRepositoryMock
            ->expects($this->once())
            ->method('createUser')
            ->with($userData)
            ->willReturn($createdUser);

        // Act
        $result = $this->authService->registerUser($userData);

        // Assert - Verificamos que el usuario tenga un ID asignado
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id_usuario']);
    }

    /**
     * Test: Verifica que se pueda solicitar un reset de contraseña
     * 
     * Escenario: Usuario olvidó su contraseña e ingresa su email
     * Resultado esperado: Debe generar un token, guardarlo y enviar un email
     * 
     * @test
     */
    public function it_requests_password_reset_successfully(): void
    {
        // Arrange
        $email = 'test@example.com';
        $userData = [
            'id_usuario' => 1,
            'email_usuario' => $email,
            'nombre_usuario' => 'Test User'
        ];

        // Configuramos que encuentre el usuario por email
        $this->authRepositoryMock
            ->method('findUserByEmail')
            ->with($email)
            ->willReturn($userData);

        // Configuramos que actualice el token en la BD
        $this->authRepositoryMock
            ->method('updateUserToken')
            ->willReturn(true);

        // Act
        $result = $this->authService->requestPasswordReset($email);

        // Assert - Verificamos que se generó el token y se envió el email
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('token', $result['user']); // El token debe estar en el usuario
    }

    /**
     * Test: Verifica que no se pueda solicitar reset con email vacío
     * 
     * Escenario: Usuario no ingresa email
     * Resultado esperado: Debe lanzar excepción indicando que el email es requerido
     * 
     * @test
     */
    public function it_throws_exception_when_resetting_password_with_empty_email(): void
    {
        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Debes ingresar un correo válido');

        // Act
        $this->authService->requestPasswordReset('');
    }

    /**
     * Test: Verifica que no se pueda solicitar reset para usuario inexistente
     * 
     * Escenario: El email ingresado no existe en la base de datos
     * Resultado esperado: Debe lanzar excepción indicando que el usuario no existe
     * 
     * @test
     */
    public function it_throws_exception_when_user_not_found_for_password_reset(): void
    {
        // Arrange - Simulamos que no se encuentra el usuario
        $this->authRepositoryMock
            ->method('findUserByEmail')
            ->willReturn(null);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('El usuario no existe en el sistema');

        // Act
        $this->authService->requestPasswordReset('notfound@test.com');
    }

    /**
     * Test: Verifica que se pueda resetear la contraseña correctamente
     * 
     * Escenario: Usuario tiene un token válido y quiere cambiar su contraseña
     * Resultado esperado: La contraseña debe actualizarse y retornar true
     * 
     * @test
     */
    public function it_resets_password_successfully(): void
    {
        // Arrange
        $userId = 1;
        $token = 'valid-token';
        $newPassword = 'newPassword123';

        // Configuramos que encuentre el usuario
        $this->authRepositoryMock
            ->method('findUserById')
            ->willReturn(['id_usuario' => $userId]);

        // Configuramos que el token sea válido
        $this->authRepositoryMock
            ->method('verifyUserToken')
            ->willReturn(true);

        // Configuramos que la actualización sea exitosa
        $this->authRepositoryMock
            ->method('updateUserPassword')
            ->willReturn(true);

        // Act
        $result = $this->authService->resetPassword($userId, $token, $newPassword);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test: Verifica que no se pueda resetear con token inválido
     * 
     * Escenario: El token proporcionado no coincide o ya expiró
     * Resultado esperado: Debe lanzar excepción de token inválido
     * 
     * @test
     */
    public function it_throws_exception_when_resetting_with_invalid_token(): void
    {
        // Arrange
        $userId = 1;
        $token = 'invalid-token';
        $newPassword = 'newPassword123';

        $this->authRepositoryMock
            ->method('findUserById')
            ->willReturn(['id_usuario' => $userId]);

        // El token NO es válido
        $this->authRepositoryMock
            ->method('verifyUserToken')
            ->willReturn(false);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('El token no coincide o ya expiró');

        // Act
        $this->authService->resetPassword($userId, $token, $newPassword);
    }

    /**
     * Test: Verifica que no se pueda resetear sin proporcionar contraseña
     * 
     * Escenario: Usuario envía token pero no ingresa nueva contraseña
     * Resultado esperado: Debe lanzar excepción indicando que la contraseña es requerida
     * 
     * @test
     */
    public function it_throws_exception_when_resetting_with_empty_password(): void
    {
        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Debes ingresar una contraseña');

        // Act
        $this->authService->resetPassword(1, 'some-token', '');
    }

    /**
     * Test: Verifica que no se pueda resetear sin token
     * 
     * Escenario: Usuario intenta cambiar contraseña sin token válido
     * Resultado esperado: Debe lanzar excepción indicando que falta el token
     * 
     * @test
     */
    public function it_throws_exception_when_resetting_with_empty_token(): void
    {
        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No hay un token válido');

        // Act
        $this->authService->resetPassword(1, '', 'newPassword123');
    }
}

