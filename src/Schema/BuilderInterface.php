<?php

namespace Siarko\DbModelApi\Schema;

interface BuilderInterface
{
    /**
     * @return array with database schema structure
     */
    public function build(): array;

}