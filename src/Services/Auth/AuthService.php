<?php
declare(strict_types=1);

namespace Src\Services\Auth;

use Src\Repositories\Auth\AuthRepositoryInterface;
use Src\Helpers\JwtHelper;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Exceptions\NestedValidationException;

class AuthService
{
    private AuthRepositoryInterface $authRepository;
    private JwtHelper $jwtHelper;
    private string $secretKey;
    private $emailService;

    public function __construct(
        AuthRepositoryInterface $authRepository,
        string $secretKey,
        $emailService
    ) {
        if (empty($secretKey)) {
            throw new \InvalidArgumentException('La clave secreta no puede estar vacía');
        }

        $this->authRepository = $authRepository;
        $this->secretKey = $secretKey;
        $this->emailService = $emailService;
        $this->jwtHelper = new JwtHelper();
    }

    public function authenticateUser(string $identifier, string $password): array
    {
        $user = $this->authRepository->findUserByCredentials($identifier, $password);

        if (!$user) {
            throw new \Exception('Documento, código o contraseña incorrecto', 404);
        }

        $token = $this->jwtHelper->getTokenJWT($this->secretKey, $user, "+3 hour");

        return [
            'user' => $user,
            'token' => $token
        ];
    }

    public function registerUser(array $userData): array
    {
        try {
            return $this->authRepository->createUser($userData);
        } catch (ValidationException | NestedValidationException $e) {
            throw new \Exception(json_encode(array_values($e->getMessages())), 400);
        }
    }

    public function requestPasswordReset(string $email): array
    {
        if (empty($email)) {
            throw new \Exception('Debes ingresar un correo válido', 400);
        }

        $user = $this->authRepository->findUserByEmail($email);

        if (!$user) {
            throw new \Exception('El usuario no existe en el sistema', 400);
        }

        $token = $this->jwtHelper->getTokenJWT($this->secretKey, $user, '+1 hour');
        
        $updated = $this->authRepository->updateUserToken((int)$user['id_usuario'], $token);

        if (!$updated) {
            throw new \Exception('No se pudo procesar la solicitud de recuperación', 400);
        }

        $user['token'] = $token;

        $emailResult = $this->emailService->sendEmail(
            $user['email_usuario'], 
            null,
            'Solicitud de recuperación de contraseña',
            null,
            true,
            'email/forgotpass.html',
            $user,
            null
        );

        if ($emailResult['statusCode'] !== 200) {
            throw new \Exception('El correo no pudo enviarse', 400);
        }

        return [
            'user' => $user,
            'email' => $emailResult
        ];
    }

    public function resetPassword(int $userId, string $token, string $newPassword): bool
    {
        if (empty($newPassword)) {
            throw new \Exception('Debes ingresar una contraseña', 400);
        }

        if (empty($token)) {
            throw new \Exception('No hay un token válido', 400);
        }

        $user = $this->authRepository->findUserById($userId);

        if (!$user) {
            throw new \Exception('El usuario no existe', 400);
        }

        $isValidToken = $this->authRepository->verifyUserToken($userId, $token);

        if (!$isValidToken) {
            throw new \Exception('El token no coincide o ya expiró', 400);
        }

        $updated = $this->authRepository->updateUserPassword($userId, $newPassword);

        if (!$updated) {
            throw new \Exception('Error al cambiar tu contraseña', 400);
        }

        return true;
    }

    public function validateToken(string $token): object
    {
        return $this->jwtHelper->validateToken($token, $this->secretKey);
    }
}
