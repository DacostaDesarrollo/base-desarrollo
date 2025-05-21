<?php

namespace Src\Models;
use Illuminate\Database\Eloquent\Model;

use Src\Helpers\HostHelper as HostHelper;

class Product extends Model{
    protected $table = 'productos'; // Nombre de la tabla en la base de datos
    protected $perPage = 15;
    protected $fillable = [
        'id_producto',
        'nombre_producto',
        'slug_producto',
        'descripcion_producto',
        'estado_factura',
        'valor_total_factura',
        'valor_producto',
        'valor_descuento',
        'tipo_producto',
        'estado_producto',
    ]; // Campos que se pueden rellenar
    public $timestamps = false;
    /**
     * llave primaria asiciada a la tabla.
     *
     * @var string
     */

    protected $primaryKey = 'id_producto';
   
}