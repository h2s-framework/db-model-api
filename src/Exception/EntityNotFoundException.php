<?php

namespace Siarko\DbModelApi\Exception;

use Throwable;

class EntityNotFoundException extends \Exception
{

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct("Entity was not found: ".$message, $code, $previous);
    }

}