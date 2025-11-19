<?php
declare(strict_types=1);

namespace Src\Repositories\Auth;

use Src\Models\Usuario;
use Src\Validation\Rules\UniqueField;
use Src\Helpers\HostHelper;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Factory;

class EloquentAuthRepository implements AuthRepositoryInterface
{
    public function __construct()
    {
        $this->configureValidationTranslations();
    }

    public function findUserByCredentials(string $identifier, string $password): ?array
    {
        $user = Usuario::select(
            'id_usuario',
            'identificacion_tributaria_usuario',
            'nombre_usuario', 
            'apellido_usuario',
            'email_usuario',
            'tipo_usuario_fk',
            'tipo_usuarios.nombre_tipo',
            'tipo_usuarios.slug_tipo'
        )
        ->join('tipo_usuarios', 'usuarios.tipo_usuario_fk', '=', 'tipo_usuarios.slug_tipo')
        ->where(function ($query) use ($identifier) {
            $query->where('identificacion_tributaria_usuario', $identifier)
                  ->orWhere('email_usuario', $identifier);
        })
        ->where('password_usuario', md5($password))
        ->where('estado_usuario', 1)
        ->first();

        return $user ? $user->toArray() : null;
    }

    public function findUserByEmail(string $email): ?array
    {
        $user = Usuario::select('id_usuario', 'nombre_usuario', 'email_usuario')
            ->where('email_usuario', $email)
            ->first();

        return $user ? $user->toArray() : null;
    }

    public function findUserById(int $userId): ?array
    {
        $user = Usuario::select('id_usuario', 'nombre_usuario', 'email_usuario', 'token_usuario')
            ->where('id_usuario', $userId)
            ->first();

        return $user ? $user->toArray() : null;
    }

    public function createUser(array $userData): array
    {
        $validator = v::arrayType()
            ->key('email_usuario', 
                v::stringType()
                ->email()
                ->notEmpty()
                ->addRule(new UniqueField('usuarios', 'email_usuario'))
                ->setName('email'))
            ->key('identificacion_tributaria_usuario', 
                v::stringType()
                ->notEmpty()
                ->addRule(new UniqueField('usuarios', 'identificacion_tributaria_usuario'))
                ->setName('Número de identificación tributaria'))
            ->key('password_usuario', 
                v::stringType()
                ->length(6, null)
                ->notEmpty()
                ->noWhitespace()
                ->setName('Contraseña'));

        $validator->assert($userData);

        $userData['password_usuario'] = md5($userData['password_usuario']);
        $userData['ip_usuario'] = HostHelper::getIpUser();
        
        $user = Usuario::create($userData);
        
        return $user->toArray();
    }

    public function updateUserToken(int $userId, string $token): bool
    {
        return Usuario::where('id_usuario', $userId)
            ->update(['token_usuario' => $token]) > 0;
    }

    public function updateUserPassword(int $userId, string $password): bool
    {
        return Usuario::where('id_usuario', $userId)
            ->update(['password_usuario' => md5($password)]) > 0;
    }

    public function verifyUserToken(int $userId, string $token): bool
    {
        $user = Usuario::select('token_usuario')
            ->where('id_usuario', $userId)
            ->first();

        return $user && $user->token_usuario === $token;
    }

    private function configureValidationTranslations(): void
    {
        Factory::setDefaultInstance(
            (new Factory())
                ->withTranslator(static function (string $message): string {
                    return [
                        '{{name}} must not be empty' => '{{name}} no debe estar vacío',
                        '{{name}} must be of type string' => '{{name}} debe ser de tipo cadena',
                        '{{name}} must be valid email' => 'debe ser un correo electrónico válido',
                        '{{name}} must have a length between {{minValue}} and {{maxValue}}' => '{{name}} debe tener una longitud entre {{minValue}} y {{maxValue}}',
                        'These rules must pass for {{name}}' => 'Estas reglas deben pasar para {{name}}',
                        '{{name}} must be valid' => 'Debe ser válido',
                        'El campo ya existe en el sistema.' => 'El {{name}} ya existe en el sistema',
                        '{{name}} must not contain whitespace' => 'El {{name}} no debe contener espacios en blanco',
                        '{{name}} must be present' => '{{name}} es obligatorio',
                    ][$message];
                })
                ->withRuleNamespace('Src\\Validation\\Rules')
                ->withExceptionNamespace('Src\\Validation\\Exceptions')
        );
    }
}
