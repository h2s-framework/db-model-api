<?php

namespace Siarko\DbModelApi\Schema;

use Siarko\SqlCreator\DatabaseElement\KeyType;
use Siarko\SqlCreator\DatabaseElement\Key\AbstractColumnKey;

class Provider extends Storage
{

    public function getKeys(string $entity): array
    {
        $table = $this->getEntity($entity);
        $keys = [];
        foreach ($table->getColumns() as $column) {
            $k = $column->getKeys();
            if(count($k)){
                $keys[$column->getName()] = $k;
            }
        }
        return $keys;
    }

    public function getKeysByType(string $entity, ?KeyType $keyType){
        $table = $this->getEntity($entity);
        $keys = [];
        foreach ($table->getColumns() as $column) {
            $k = $column->getKeys();
            foreach ($k as $key) {
                if($keyType->isKey($key)){
                    if(!array_key_exists($column->getName(), $keys)){
                        $keys[$column->getName()] = [];
                    }
                    $keys[$column->getName()][] = $key;
                }
            }
        }
        return $keys;
    }

}