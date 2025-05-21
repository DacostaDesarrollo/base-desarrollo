<?php

namespace Src\Models;
use Illuminate\Database\Eloquent\Model;

use Respect\Validation\Validator as v;
use Src\Validation\Rules\UniqueField;
use Respect\Validation\Exceptions\ValidationException as ValidationException;
use Respect\Validation\Exceptions\NestedValidationException as NestedValidationException;
use Respect\Validation\Factory;

use Src\Helpers\HostHelper as HostHelper;

class PaymentsMethod extends Model{
    protected $table = 'pasarelas'; // Nombre de la tabla en la base de datos
    protected $perPage = 15;
    protected $fillable = [
        'id_pasarela',
        'nombre_pasarela'
       
    ]; // Campos que se pueden rellenar
    public $timestamps = false;
    /**
     * llave primaria asiciada a la tabla.
     *
     * @var string
     */

    protected $primaryKey = 'id_pasarela';
   
}