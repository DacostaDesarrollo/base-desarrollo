<?php

namespace Src\Models;
use Illuminate\Database\Eloquent\Model;

use Src\Helpers\HostHelper as HostHelper;

class SuscriptionInvoiceDetails extends Model{
    protected $table = 'suscripcion_factura_detalle'; // Nombre de la tabla en la base de datos
    protected $perPage = 15;
    protected $fillable = [
        'id_suscripcion_factura_detalle',
        'id_suscripcion_fk',
        'id_detalle_factura_fk',
       
    ]; // Campos que se pueden rellenar
    public $timestamps = false;
    /**
     * llave primaria asiciada a la tabla.
     *
     * @var string
     */

    protected $primaryKey = 'id_suscripcion_factura_detalle';
   
}