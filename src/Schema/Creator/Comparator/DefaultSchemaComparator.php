<?php

namespace Siarko\DbModelApi\Schema\Creator\Comparator;

use Siarko\SqlCreator\DatabaseElement\Column;
use Siarko\SqlCreator\DatabaseElement\Key\AbstractColumnKey;
use Siarko\SqlCreator\DatabaseElement\Key\Foreign;
use Siarko\SqlCreator\DatabaseElement\Key\Primary;
use Siarko\SqlCreator\DatabaseElement\Table;
use Siarko\Utils\ArrayManager;

class DefaultSchemaComparator implements SchemaComparatorInterface
{

    public function __construct(
        protected readonly ArrayManager $arrayManager
    )
    {
    }

    /**
     * @param Table[] $mainSchema
     * @param Table[] $secondarySchema
     * @return array
     */
    public function compare(array $mainSchema, array $secondarySchema): array
    {
        $diff = [];
        foreach ($secondarySchema as $tableName => $tableInstance) {
            if(!array_key_exists($tableName, $mainSchema)){
                $this->arrayManager->set([self::KEY_CREATE,$tableName], $diff, $tableInstance);
            }else{
                $mainTable = $mainSchema[$tableName];
                $columnDiff = [];
                foreach ($tableInstance->getColumns() as $column) {
                    if(!$mainTable->hasColumn($column->getName())){
                        $this->arrayManager->set([self::KEY_CREATE, $column->getName()], $columnDiff, $column);
                    }else{
                        $cd = $this->columnDiff($mainTable->getColumn($column->getName()), $column);
                        if(count($cd)){
                            $this->arrayManager->set([self::KEY_MODIFY, $column->getName()], $columnDiff, $cd);
                        }
                    }
                }
                foreach ($mainSchema[$tableName]->getColumns() as $column) {
                    if(!$tableInstance->hasColumn($column->getName())){
                        $this->arrayManager->set([self::KEY_DROP, $column->getName()], $columnDiff, $column);
                    }
                }
                if(count($columnDiff) > 0){
                    $this->arrayManager->set([self::KEY_MODIFY, $tableName], $diff, $columnDiff);
                }

            }
        }
        foreach ($mainSchema as $tableName => $tableData) {
            if(!array_key_exists($tableName, $secondarySchema)){
                $this->arrayManager->set([self::KEY_DROP, $tableName], $diff, $tableData);
            }
        }
        return $diff;
    }

    protected function columnDiff(Column $mainColumn, Column $secondaryColumn): array
    {
        $result = [];
        if($mainColumn->isAutoIncrement() !== $secondaryColumn->isAutoIncrement()){
            $result['autoIncrement'] = $secondaryColumn->isAutoIncrement();
        }
        if($mainColumn->getType()->cast(
                $mainColumn->getDefaultValue()
            ) !== $secondaryColumn->getType()->cast(
                $secondaryColumn->getDefaultValue()
            )
        ){
            $result['defaultValue'] = $secondaryColumn->getDefaultValue();
        }
        if($mainColumn->isNullable() !== $secondaryColumn->isNullable()){
            //special case for default = 0 - it
            $result['nullable'] = $secondaryColumn->isNullable();
        }
        if(!$mainColumn->getType()->equals($secondaryColumn->getType())){
            $result['type'] = $secondaryColumn->getType();
        }
        $keyDiff = $this->keyDiff($mainColumn->getKeys(), $secondaryColumn->getKeys());
        if(count($keyDiff) > 0){
            $result['keys'] = $keyDiff;
        }

        return $result;
    }

    protected function keyDiff(array $mainKeys, array $secondaryKeys): array
    {
        $diff = [];
        foreach ($secondaryKeys as $keyName => $keyInstance) {
            if(!array_key_exists($keyName, $mainKeys)){
                $this->arrayManager->set([self::KEY_CREATE, $keyName], $diff, $keyInstance);
            }
        }
        foreach ($mainKeys as $keyName => $keyInstance) {
            if(!array_key_exists($keyName, $secondaryKeys)){
                $this->arrayManager->set([self::KEY_DROP, $keyName], $diff, $keyInstance);
            }
        }
        return $diff;
    }

}