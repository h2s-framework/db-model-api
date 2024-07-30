<?php

namespace Siarko\DbModelApi\Schema\Creator\SchemaFile\AssocParser\Column\Key;

use Siarko\DbModelApi\Exception\DbSchema\File\TargetMalformed;
use Siarko\SqlCreator\DatabaseElement\Key\AbstractColumnKey;
use Siarko\SqlCreator\DatabaseElement\Key\ForeignFactory;
use Siarko\SqlCreator\DatabaseElement\Key\PrimaryFactory;

class DbSchemaFileParser implements \Siarko\SqlCreator\DatabaseElement\AssocParser\Column\Key\ColumnKeyParserInterface
{

    public function __construct(
        protected readonly PrimaryFactory $primaryFactory,
        protected readonly ForeignFactory $foreignFactory
    )
    {
    }

    public function parse(array $data): AbstractColumnKey
    {
        if($data['type'] === 'primary'){
            return $this->primaryFactory->create(['columnName' => $data['column']]);
        }else{
            $target = explode('.', $data['target']);
            if(count($target) != 2){
                throw new TargetMalformed($data['sourceTable']);
            }
            $key = $this->foreignFactory->create(['columnName' => $data['column']]);
            $key->setSourceColumn($data['column']);
            $key->setSourceTable($data['sourceTable']);
            $key->setTargetTable($target[0]);
            $key->setTargetColumn($target[1]);
            if(array_key_exists('name', $data)){
                $key->setConstraintName($data['name']);
            }else{
                $key->setConstraintName($key->getKeyName(true));
            }

            return $key;
        }
    }
}