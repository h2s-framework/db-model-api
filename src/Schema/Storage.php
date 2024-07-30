<?php

namespace Siarko\DbModelApi\Schema;

use Siarko\CacheFiles\Api\CacheSetInterface;
use Siarko\DbModelApi\Schema\Creator\SchemaWriter;
use Siarko\SqlCreator\DatabaseElement\Table;

class Storage implements StorageInterface
{

    protected const CACHE_KEY = 'live';

    private array $schema = [];

    public function __construct(
        private readonly BuilderInterface  $schemaBuilder,
        private readonly CacheSetInterface $schemaCache,
        protected readonly SchemaWriter    $schemaWriter
    )
    {
    }

    public function getSchema(): array
    {
        $this->loadSchema();
        return $this->schema;
    }

    /**
     * @param Table[] $schema
     * @param bool $updateDatabase
     * @throws \JsonException
     */
    public function setSchema(array $schema, bool $updateDatabase = true){
        if($updateDatabase){
            $this->schemaWriter->write($schema);
            $this->schemaCache->set(self::CACHE_KEY, $schema);
        }
        $this->schema = $schema;
    }

    /**
     * @param string $entity
     * @return Table|null
     */
    public function getEntity(string $entity): ?Table
    {
        if(!$this->entityExists($entity)){
            return null;
        }
        return $this->getSchema()[$entity];
    }

    /**
     * @param string $entity
     * @return bool
     */
    public function entityExists(string $entity): bool
    {
        return array_key_exists($entity, $this->getSchema());
    }

    /**
     * @throws \JsonException
     */
    protected function loadSchema(){
        if(count($this->schema) == 0){
            if($this->schemaCache->exists(self::CACHE_KEY)){
                $data = $this->schemaCache->get(self::CACHE_KEY);
            }else{
                $data = $this->schemaBuilder->build();
                $this->schemaCache->set(self::CACHE_KEY, $data);
            }
            $this->schema = $data;
        }
    }


}