<?php

namespace Src\Models;
use Illuminate\Database\Eloquent\Model;

use Respect\Validation\Validator as v;
use Src\Validation\Rules\UniqueField; // Asegúrate de importar la clase
use Respect\Validation\Exceptions\ValidationException as ValidationException;
use Respect\Validation\Exceptions\NestedValidationException as NestedValidationException;
use Respect\Validation\Factory;

use Src\Helpers\HostHelper as HostHelper;

class Usuario extends Model{
    protected $table = 'usuarios'; // Nombre de la tabla en la base de datos
    protected $perPage = 4;
    protected $fillable = [
        'identificacion_tributaria_usuario',
        'apellido_usuario',
        'direccion_usuario',
        'nombre_usuario',
        'cedula_usuario',
        'email_usuario ', 
        'pais_usuario', 
        'departamento_usuario',
        'ciudad_usuario', 
        'password_usuario',
        'email_usuario',
        'ip_usuario',
        'cargo_usuario',
        'tel_usuario',
        'autorizo_usuario',
        'tipo_usuario_fk'
    ]; // Campos que se pueden rellenar
    public $timestamps = false;
    /**
     * The primary key associated with the table.
     *
     * @var string
     */

    protected $primaryKey = 'id_usuario';
    public static function validateAndCreateUserAdmin(array $data){
        static::translateError();
       
        $validator = v::arrayType()
        ->key('email_usuario', 
            v::stringType()
            ->Email()->
            notEmpty()
            ->addRule(new UniqueField('usuarios', 'email_usuario'))
            ->setName('email'))
        ->key('identificacion_tributaria_usuario', 
            v::stringType()
            ->notEmpty()
            ->addRule(new UniqueField('usuarios', 'identificacion_tributaria_usuario'))
            ->setName('Número de identificación tributaria'))
        ->key('password_usuario', 
            v::stringType()
            ->length(6, null) // Mínimo 6 caracteres
            ->notEmpty() // El campo no puede ser vacio
            ->noWhitespace() // sin espacios
            //->containsSpecialChars(1) // Al menos 1 carácter especial
            //->containsDigits(1) // Al menos 1 dígito
            //->containsUpperCase(1) // Al menos 1 letra mayúscula
            //->containsLowerCase(1) // Al menos 1 letra minúscula
            ->setName('Contraseña'));
        
        if ($validator->assert($data) == null) {
           
            $data['password_usuario'] = md5($data['password_usuario']);
            $data['ip_usuario'] = HostHelper::getIpUser();
            
            return static::create($data);

        }else{
             throw new NestedValidationException($validator);
        }
    }
    public static function validateAndCreateAsociado(array $data){
        static::translateError();
       
        $validator = v::arrayType()
        ->key('email_usuario', 
            v::stringType()
            ->Email()->
            notEmpty()
            ->addRule(new UniqueField('usuarios', 'email_usuario'))
            ->setName('email'))
        ->key('codigo_usuario', 
            v::stringType()
            ->notEmpty()
            ->addRule(new UniqueField('usuarios', 'codigo_usuario'))
            ->setName('Codigo'))
        ->key('asociacion_camara_archivo_usuario', 
           v::numericVal()
            ->notEmpty()
            ->setName('Archivo asociación camara'))
        ->key('password_usuario', 
            v::stringType()
            ->length(6, null) // Mínimo 6 caracteres
            ->notEmpty() // El campo no puede ser vacio
            ->noWhitespace() // sin espacios
            //->containsSpecialChars(1) // Al menos 1 carácter especial
            //->containsDigits(1) // Al menos 1 dígito
            //->containsUpperCase(1) // Al menos 1 letra mayúscula
            //->containsLowerCase(1) // Al menos 1 letra minúscula
            ->setName('Contraseña'));
        
        if ($validator->assert($data) == null) {
           
            $data['password_usuario'] = md5($data['password_usuario']);
            $data['ip_usuario'] = HostHelper::getIpUser();
            
            return static::create($data);

        }else{
             throw new NestedValidationException($validator);
        }
    }



    public static function validateAndCreateAdhesion(array $data){
        static::translateError();
       
        $validator = v::arrayType()
        ->key('email_usuario', 
            v::stringType()
            ->Email()->
            notEmpty()
            ->addRule(new UniqueField('usuarios', 'email_usuario'))
            ->setName('Email'))
        ->key('codigo_usuario', 
            v::stringType()
            ->notEmpty()
            ->addRule(new UniqueField('usuarios', 'codigo_usuario'))
            ->setName('Código'))

        ->key('identificacion_tributaria_usuario', 
            v::stringType()
            ->notEmpty()
            ->addRule(new UniqueField('usuarios', 'identificacion_tributaria_usuario'))
            ->setName('Número de identificación tributaria'))

        ->key('nombre_usuario', 
            v::stringType()
            ->notEmpty()
            ->setName('Nombre o razón Social'))

        ->key('pais_usuario', 
            v::numericVal()
            ->notEmpty()
            ->setName('País'))

        ->key('password_usuario', 
            v::stringType()
            ->length(6, null) // Mínimo 6 caracteres
            ->notEmpty() // El campo no puede ser vacio
            ->noWhitespace() // sin espacios
            //->containsSpecialChars(1) // Al menos 1 carácter especial
            //->containsDigits(1) // Al menos 1 dígito
            //->containsUpperCase(1) // Al menos 1 letra mayúscula
            //->containsLowerCase(1) // Al menos 1 letra minúscula
            ->setName('Contraseña'));
        
        if ($validator->assert($data) == null) {
           
            $data['password_usuario'] = md5($data['password_usuario']);
            $data['ip_usuario'] = HostHelper::getIpUser();
            
            return static::create($data);

        }else{
             throw new NestedValidationException($validator);
        }
    }

    public static function translateError():void {
    
        Factory::setDefaultInstance(
            (new Factory())
                ->withTranslator(static function (string $message): string {
                    //Si hay un mensaje sin traducir arrojara un error
                    //var_dump($message);
                    return [
                        '{{name}} must not be empty' => '{{name}} no debe estar vacío',
                        '{{name}} must be of type string' => '{{name}} debe ser de tipo cadena.',
                        '{{name}} must be valid email' => 'debe ser un correo electrónico válido',
                        '{{name}} must have a length between {{minValue}} and {{maxValue}}' => '{{name}} debe tener una longitud entre {{minValue}} y {{maxValue}}',
                        'These rules must pass for {{name}}' => 'Estas reglas debe de pasar por {{name}}',
                        '{{name}} must be valid' => 'Debe ser válido',
                        'El campo ya existe en el sistema.' => 'El {{name}} ya existe en el sistema.',
                        '{{name}} must not contain whitespace' => 'El {{name}} no debe contener espacios en blanco',
                        '{{name}} must be present' => '{{name}} es obligatorio.',
                    ][$message];
                })
                ->withRuleNamespace('Src\\Validation\\Rules')
                ->withExceptionNamespace('Src\\Validation\\Exceptions')
        );
    }
    public static function getCodeUser():void {
    
       
        
    }
}