<?php

namespace Siarko\DbModelApi\Exception;

use Throwable;

class DbModelSingleFetchException extends \Exception
{
    public function __construct(
        $message = "",
        $code = 0,
        Throwable $previous = null
    )
    {
        parent::__construct("Expecting single model instance, multiple found: ".$message, $code, $previous);
    }


}