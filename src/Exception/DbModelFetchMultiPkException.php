<?php

namespace Siarko\DbModelApi\Exception;

use Throwable;

class DbModelFetchMultiPkException extends \Exception
{
    public function __construct($message = "Trying to fetch multi-primary-key indexed column with single value", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}