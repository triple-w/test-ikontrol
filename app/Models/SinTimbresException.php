<?php

namespace App\Exceptions;

use Exception;

class SinTimbresException extends Exception
{
    public function __construct($message = 'No hay timbres disponibles para este RFC.')
    {
        parent::__construct($message);
    }
}
