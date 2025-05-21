<?php
namespace Src\Helpers;


class SqlHelper {
    /**
     * Obtener el string del sql actual
     */
    public function toSql($query){
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        // Combina las consultas SQL y los valores vinculados para obtener la consulta completa
        $fullSql = vsprintf(str_replace(['%', '?'], ['%%', '%s'], $sql), $bindings);
        return $fullSql;
    }

    
}
