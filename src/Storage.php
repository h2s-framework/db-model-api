<?php

namespace Siarko\DbModelApi;

use Siarko\DbModelApi\Exception\DbModelFetchException;
use Siarko\DbModelApi\Exception\DbModelFetchMultiPkException;
use Siarko\DbModelApi\Exception\DbModelSingleFetchException;
use Siarko\DbModelApi\Exception\EntityNotFoundException;
use Siarko\DbModelApi\Schema\Provider;
use Siarko\DbModelApi\Storage\StorageContextInterface;
use Siarko\DbModelApi\BasicCollectionFactory;
use Siarko\SqlCreator\DatabaseElement\KeyType;
use Siarko\SqlCreator\Sql;

class Storage implements StorageInterface
{

    public function __construct(
        protected readonly StorageContextInterface $storageContext,
        protected readonly Provider $schemaProvider,
        protected readonly BasicCollectionFactory $collectionFactory
    )
    {
    }


    /**
     * Create collection for specified model
     * @param string|AbstractModel $modelClass
     * @param mixed $condition
     * @return BasicCollection
     * @throws DbModelFetchMultiPkException
     * @throws \Siarko\SqlCreator\Exceptions\IncorrectConditionData
     * @throws \Siarko\SqlCreator\Exceptions\IncorrectConditionType
     */
    public function find(string|AbstractModel $modelClass, mixed $condition = []): BasicCollection
    {
        $collection = $this->collection($modelClass);
        if(is_array($condition)){
            $collection->getQuery()->where($condition);
        }else{
            $keyData = $this->schemaProvider->getKeysByType($modelClass::getEntityName(), KeyType::PRIMARY);
            if(count($keyData) > 1){
                throw new DbModelFetchMultiPkException();
            }
            $pkColumn = key($keyData); //get PK columnName
            $collection->getQuery()->where([$pkColumn => $condition]);
        }
        return $collection;
    }

    /**
     * Fetch and expect only one row
     * @param string|AbstractModel $modelClass
     * @param mixed|array $condition
     * @return AbstractModel
     * @throws DbModelFetchMultiPkException
     * @throws DbModelSingleFetchException
     * @throws EntityNotFoundException
     * @throws \Siarko\SqlCreator\Exceptions\IncorrectConditionData
     * @throws \Siarko\SqlCreator\Exceptions\IncorrectConditionType
     */
    public function one(string|AbstractModel $modelClass, mixed $condition = []): AbstractModel
    {
        $result = $this->find($modelClass, $condition)->load();
        $count = count($result);
        if($count == 0){
            throw new EntityNotFoundException($modelClass);
        }
        if($count > 1){
            throw new DbModelSingleFetchException($modelClass);
        }
        return current($result);
    }

    /**
     * @param string|AbstractModel $modelClass
     * @return BasicCollection
     */
    public function collection(string|AbstractModel $modelClass): BasicCollection
    {
        return $this->collectionFactory->create(['model' => $modelClass]);
    }

    public function save(AbstractModel $model)
    {
        if($model->isNew()){
            $this->insert($model);
        }else{
            $this->update($model);
        }
    }

    protected function insert(AbstractModel $model): int
    {
        if(count($model->getChanges()) > 0){
            $insert = Sql::insert($model->getChanges())
                ->into($model::getEntityName());
            return $this->storageContext->query($insert)->count();
        }
        return 0;
    }

    protected function update(AbstractModel $model): int
    {
        if(count($model->getChanges()) > 0){
            $update = Sql::update($model::getEntityName())
                ->set($model->getChanges())
                ->where($this->getKeyCondition($model));
            return $this->storageContext->query($update)->count();
        }
        return 0;
    }

    public function delete(AbstractModel $model): int
    {
        $condition = $this->getKeyCondition($model);
        $delete = Sql::delete()->from($model::getEntityName())->where($condition);
        return $this->storageContext->query($delete)->count();
    }

    protected function getKeyCondition(AbstractModel $model):array
    {
        $result = [];
        $keyData = $this->schemaProvider->getKeysByType($model::getEntityName(), KeyType::PRIMARY);
        foreach ($keyData as $column => $k) {
            $result[$column] = $model->getData($column);
        }
        return $result;
    }


}