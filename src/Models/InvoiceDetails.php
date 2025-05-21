<?php

namespace Src\Models;
use Illuminate\Database\Eloquent\Model;

use Src\Helpers\HostHelper as HostHelper;

class InvoiceDetails extends Model{
    protected $table = 'factura_detalles'; // Nombre de la tabla en la base de datos
    protected $perPage = 15;
    protected $fillable = [
        'id_factura_detalle',
        'id_factura_fk',
        'producto_factura_detalle',
        'cantidad_producto_detalle',
        'valor_unitario_detalle',
        'valor_facturado_detalle'
       
    ]; // Campos que se pueden rellenar 
    public $timestamps = false;
    /**
     * llave primaria asiciada a la tabla.
     *
     * @var string
     */

    protected $primaryKey = 'id_factura_detalle';
   
}