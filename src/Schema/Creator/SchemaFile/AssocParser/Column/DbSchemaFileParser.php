<?php

namespace Siarko\DbModelApi\Schema\Creator\SchemaFile\AssocParser\Column;

use Siarko\SqlCreator\DatabaseElement\Column;
use Siarko\SqlCreator\DatabaseElement\ColumnFactory;
use Siarko\SqlCreator\DatabaseElement\DataTypeProvider;
use Siarko\SqlCreator\Language\Tokens\Token;

class DbSchemaFileParser implements \Siarko\SqlCreator\DatabaseElement\AssocParser\Column\ColumnParserInterface
{

    const KEY_TYPE = 'type';
    const KEY_AUTO_INCREMENT = 'ai';
    const KEY_NULLABLE = 'nullable';
    const KEY_DEFAULT = 'default';

    public function __construct(
        protected readonly ColumnFactory $columnFactory,
        protected readonly DataTypeProvider $dataTypeProvider
    )
    {
    }

    public function parse(array $data): Column
    {
        $column = $this->columnFactory->create(['name' => $data['name']]);
        $column->setTableName($data['tableName']);
        $column->setType($this->dataTypeProvider->parseString($data[self::KEY_TYPE]));
        if(array_key_exists(self::KEY_AUTO_INCREMENT, $data)){$column->setAutoIncrement($data[self::KEY_AUTO_INCREMENT]);}
        if($column->isAutoIncrement()){
            $column->setNullable(false);
            $column->setNoDefaultValue();
        }else{
            if(array_key_exists(self::KEY_NULLABLE, $data)){$column->setNullable($data[self::KEY_NULLABLE]);}
            if(array_key_exists(self::KEY_DEFAULT, $data)){
                $column->setDefaultValue($data[self::KEY_DEFAULT]);
            }elseif ($column->isNullable()){
                $column->setDefaultValue(null);
            }
        }
        return $column;
    }
}