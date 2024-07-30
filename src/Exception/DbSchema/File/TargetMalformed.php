<?php

namespace Siarko\DbModelApi\Exception\DbSchema\File;

use Throwable;

class TargetMalformed extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct("Target incorrectly specified for FK on table: ".$message, $code, $previous);
    }


}