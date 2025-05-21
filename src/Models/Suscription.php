<?php

namespace Src\Models;
use Illuminate\Database\Eloquent\Model;

use Src\Helpers\HostHelper as HostHelper;

class Suscription extends Model{
    protected $table = 'suscripciones'; // Nombre de la tabla en la base de datos
    protected $perPage = 15;
    protected $fillable = [
        'id_suscripcion',
        'producto_suscripcion',
        'id_usuario_suscripcion',
        'estado_pago_suscripcion',
        'fecha_suscripcion',
        'fecha_fin_suscripcion'
       
    ]; // Campos que se pueden rellenar
    public $timestamps = false;
    /**
     * llave primaria asiciada a la tabla.
     *
     * @var string
     */

    protected $primaryKey = 'id_suscripcion';
   
}