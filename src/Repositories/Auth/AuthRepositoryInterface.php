<?php
declare(strict_types=1);

namespace Src\Repositories\Auth;

interface AuthRepositoryInterface
{
    public function findUserByCredentials(string $identifier, string $password): ?array;
    
    public function findUserByEmail(string $email): ?array;
    
    public function findUserById(int $userId): ?array;
    
    public function createUser(array $userData): array;
    
    public function updateUserToken(int $userId, string $token): bool;
    
    public function updateUserPassword(int $userId, string $password): bool;
    
    public function verifyUserToken(int $userId, string $token): bool;
}