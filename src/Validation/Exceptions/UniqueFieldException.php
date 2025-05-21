<?php

namespace Src\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

final class UniqueFieldException extends ValidationException
{
    protected $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => 'El campo ya existe en el sistema.',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => 'Ha fallado la validación del campo.',
        ],
    ];
}