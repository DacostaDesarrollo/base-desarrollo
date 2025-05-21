<?php

namespace Src\Validation\Rules;

use Respect\Validation\Rules\AbstractRule;
use Illuminate\Database\Capsule\Manager as Capsule;

final class UniqueField extends AbstractRule
{
    private $table;
    private $column;

    public function __construct($table, $column)
    {
        $this->table = $table;
        $this->column = $column;
    }

    public function validate($input):bool
    {   
        return !Capsule::table($this->table)
            ->where($this->column, $input)
            ->exists();
    }
    
}