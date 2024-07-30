<?php

namespace Siarko\DbModelApi;

interface StorageInterface
{

    /**
     * Fetch model instances from storage
     * @param string $modelClass
     * @param mixed $condition
     * @return BasicCollection
     */
    public function find(string $modelClass, mixed $condition = []): BasicCollection;

    /**
     * Fetch model instance from storage and expect one result
     * @param string $modelClass
     * @param mixed $condition
     * @return AbstractModel|null
     */
    public function one(string $modelClass, mixed $condition = []): ?AbstractModel;

    /**
     * Save model instance to storage
     * @param AbstractModel $model
     * @return mixed
     */
    public function save(AbstractModel $model);

    /**
     * Delete model instance from storage
     * @param AbstractModel $model
     * @return int
     */
    public function delete(AbstractModel $model): int;
}