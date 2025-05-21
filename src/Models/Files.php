<?php

namespace Src\Models;
use Illuminate\Database\Eloquent\Model;

use Respect\Validation\Validator as v;
use Src\Validation\Rules\UniqueField; // Asegúrate de importar la clase
use Respect\Validation\Exceptions\ValidationException as ValidationException;
use Respect\Validation\Exceptions\NestedValidationException as NestedValidationException;
use Respect\Validation\Factory;


class Files extends Model{
    protected $table = 'archivos'; // Nombre de la tabla en la base de datos
    protected $perPage = 15;
    protected $fillable = [
        'id_archivo',
        'nombre_archivo',
        'ruta_archivo',
        'tipo_archivo',
        'extension_archivo',
        'tamano_archivo',
        'fecha_subida_archivo'
       
    ]; // Campos que se pueden rellenar
    public $timestamps = false;
    /**
     * The primary key associated with the table.
     *
     * @var string
     */

    protected $primaryKey = 'id_archivo';
   
}