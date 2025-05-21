<?php

namespace Src\Models;
use Illuminate\Database\Eloquent\Model;

use Respect\Validation\Validator as v;
use Src\Validation\Rules\UniqueField;
use Respect\Validation\Exceptions\ValidationException as ValidationException;
use Respect\Validation\Exceptions\NestedValidationException as NestedValidationException;
use Respect\Validation\Factory;

use Src\Helpers\HostHelper as HostHelper;

class Payments extends Model{
    protected $table = 'pagos'; // Nombre de la tabla en la base de datos
    protected $perPage = 15;
    protected $fillable = [
        'id_pago',
        'moneda_pago',
        'pasarela_pago_fk',
        'valor_pago',
        'fecha_pago',
        'estado_pago_pasarela',
        'codigo_venta_pasarela',
        'iva_pago',
        'arreglo_pago_pasarela',
        'tipo_metodo_de_pago_pasarela',
        'metodo_pago_pasarela',
        'estado_pago',
        'id_usuario_pago_fk',
       
    ]; // Campos que se pueden rellenar
    public $timestamps = false;
    /**
     * llave primaria asiciada a la tabla.
     *
     * @var string
     */

    protected $primaryKey = 'id_pago';
   
}