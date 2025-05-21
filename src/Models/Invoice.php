<?php

namespace Src\Models;
use Illuminate\Database\Eloquent\Model;

use Src\Helpers\HostHelper as HostHelper;

class Invoice extends Model{
    protected $table = 'facturas'; // Nombre de la tabla en la base de datos
    protected $perPage = 15;
    protected $fillable = [
        'id_factura',
        'codigo_factura',
        'descripcion_factura',
        'comentarios_factura',
        'estado_factura',
        'valor_total_factura',
        'fecha_creacion_factura',
        'fecha_actualizacion_factura',
        'fecha_pago_factura',
        'id_pago_fk',
        'id_usuario_fk',
        'facturar_a_factura',
        'identificacion_factura',
        'ciudad_factura',
        'pais_factura',
        'direccion_factura',
        'email_factura',
       
    ]; // Campos que se pueden rellenar
    public $timestamps = false;
    /**
     * llave primaria asiciada a la tabla.
     *
     * @var string
     */

    protected $primaryKey = 'id_factura';
   
}