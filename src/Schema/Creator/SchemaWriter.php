<?php

namespace Siarko\DbModelApi\Schema\Creator;

use Siarko\DbModelApi\Schema\BuilderInterface;
use Siarko\DbModelApi\Storage\StorageContextInterface;
use Siarko\SqlCreator\AlterTable;
use Siarko\SqlCreator\CreateTable;
use Siarko\SqlCreator\DatabaseElement\Column;
use Siarko\SqlCreator\DatabaseElement\Key\AbstractColumnKey;
use Siarko\SqlCreator\DatabaseElement\Table;
use Siarko\SqlCreator\Drop;
use Siarko\Utils\ArrayManager;

class SchemaWriter
{

    /**
     * @var Table[]
     */
    protected array $currentSchema = [];

    /**
     * @param BuilderInterface $schemaBuilder
     * @param StorageContextInterface $storageContext
     * @param bool $destructive
     */
    public function __construct(
        protected readonly BuilderInterface $schemaBuilder,
        protected readonly StorageContextInterface $storageContext,
        protected bool $destructive = false
    )
    {
    }

    /**
     * @return bool
     */
    public function isDestructive(): bool
    {
        return $this->destructive;
    }

    /**
     * @param bool $destructive
     * @return SchemaWriter
     */
    public function setDestructive(bool $destructive): static
    {
        $this->destructive = $destructive;
        return $this;
    }


    /**
     * @param Table[] $schema
     * @throws \Exception
     */
    public function write(array $schema)
    {
        $this->currentSchema = $this->schemaBuilder->build();
        try{
            $this->storageContext->query("SET foreign_key_checks = 0")->fetchAll();
            $this->processUpdates($schema);
        }catch (\Exception $exception){
            $this->storageContext->query("SET foreign_key_checks = 1")->fetchAll();
            throw $exception;
        }
        $this->storageContext->query("SET foreign_key_checks = 1")->fetchAll();
    }

    /**
     * @param Table[] $schema
     */
    protected function processUpdates(array $schema)
    {
        $createdCount = 0;
        //CREATE NEW TABLES
        $newTables = array_diff_key($schema, $this->currentSchema);
        foreach ($newTables as $newTable) {
            $this->createTable($newTable);
            $createdCount++;
        }
        //UPDATE/DROP EXISTING TABLES/COLUMNS
        foreach ($this->currentSchema as $tableName => $tableData) {
            if(array_key_exists($tableName, $schema)){
                $this->updateTable($schema[$tableName]);
            }else if($this->isDestructive()){//table not exists in new schema
                $this->deleteTable($tableName);
            }
        }
    }

    /**
     * @param Table $table
     * @return array
     */
    protected function createTable(Table $table): array
    {
        $query = new CreateTable($table);
        return $this->storageContext->query($query)->fetchAll();
    }

    /**
     * @param Table $newTableData
     */
    protected function updateTable(Table $newTableData){
        $currentTableData = $this->currentSchema[$newTableData->getName()];
        foreach ($newTableData->getColumns() as $columnName => $columnData) {
            if($currentTableData->hasColumn($columnName)){ //column exists currently -> check validity
                if(!$currentTableData->getColumn($columnName)->matches($columnData, false)){
                    $this->updateColumn($columnData);
                }
                if(!$currentTableData->getColumn($columnName)->keysMatch($columnData)){
                    $this->updateKeys($columnData);
                }
            }else{ //new column
                $this->createColumn($columnData);
            }
        }
        if($this->isDestructive()){
            foreach ($currentTableData->getColumns() as $columnName => $data) {
                if(!$newTableData->hasColumn($columnName)){ //column does not exist in new schema -> remove it
                    $this->deleteColumn($data);
                }
            }
        }
    }

    /**
     * @param string $tableName
     * @return array
     */
    protected function deleteTable(string $tableName): array
    {
        $query = (new Drop())->table($tableName);
        return $this->storageContext->query($query)->fetchAll();
    }

    /**
     * @param Column $column
     */
    protected function deleteColumn(Column $column)
    {
        $query = new AlterTable($column->getTableName());
        foreach ($column->getKeys() as $key) {
            $query->drop($key);
        }
        $this->storageContext->query($query);
        $this->storageContext->query((new AlterTable($column->getTableName()))->drop($column))
            ->fetchAll();
    }

    /**
     * @param Column $columnData
     */
    protected function createColumn(\Siarko\SqlCreator\DatabaseElement\Column $columnData)
    {
        $this->storageContext->query(
            (new AlterTable($columnData->getTableName()))->add($columnData)
        )->fetchAll();
    }

    /**
     * @param Column $columnData
     */
    protected function updateColumn(\Siarko\SqlCreator\DatabaseElement\Column $columnData)
    {
        $this->storageContext->query(
            (new AlterTable($columnData->getTableName()))->modify($columnData)
        )->fetchAll();
    }

    protected function updateKeys(Column $columnData)
    {
        $query = new AlterTable($columnData->getTableName());
        $existingKeys = $this->currentSchema[$columnData->getTableName()]
            ->getColumn($columnData->getName())->getKeys();
        $dataChanged = false;
        foreach ($columnData->getKeys() as $newKey) {
            $exists = array_filter($existingKeys, function($key) use ($newKey){ return $key->matches($newKey); });
            if(count($exists) == 0){ //new key
                $query->add($newKey);
                $dataChanged = true;
            }
        }
        if($this->isDestructive()){
            foreach ($existingKeys as $existingKey) {
                $exists = array_filter($columnData->getKeys(), function($key) use ($existingKey){ return $key->matches($existingKey); });
                if(count($exists) == 0){ //new key
                    $query->drop($existingKey);
                    $dataChanged = true;
                }
            }
        }
        if($dataChanged){
            $this->storageContext->query($query)->fetchAll();
        }
    }

}