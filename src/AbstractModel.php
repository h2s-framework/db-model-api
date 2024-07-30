<?php

namespace Siarko\DbModelApi;

use Siarko\DbModelApi\Model\KeyStoreObject;

abstract class AbstractModel extends KeyStoreObject
{

    protected bool $isNew = true;

    public function __construct(array $data = [])
    {
        foreach ($data as $column => $value) {
            $this->setData($column, $value);
        }
    }

    public function __load(array $data)
    {
        $this->__setNew(false);
        foreach ($data as $column => $value) {
            $this->data[$column] = $value;
        }
    }

    public function getChanges(): array
    {
        return $this->changedData;
    }

    public function __setNew(bool $flag){
        $this->isNew = $flag;
    }

    public function isNew(): bool
    {
        return $this->isNew;
    }

    public static function getEntityName(): string{
        return strtolower(array_reverse(explode('\\', static::class))[0]);
    }


}