<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class CompteNotFoundException extends Exception
{
    public function __construct(string $message = "Compte non trouvé", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
