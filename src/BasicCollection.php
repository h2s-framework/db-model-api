<?php

namespace Siarko\DbModelApi;

use Siarko\DbModelApi\Storage\StorageContextInterface;
use Siarko\SqlCreator\SelectiveQuery;
use Siarko\SqlCreator\Sql;
use Siarko\Api\Factory\FactoryProviderInterface;

class BasicCollection
{
    private SelectiveQuery $query;

    private array $items = [];

    private bool $loaded = false;

    /**
     * @param string|AbstractModel $model
     * @param StorageContextInterface $storageContext
     * @param FactoryProviderInterface $factoryProvider
     */
    public function __construct(
        protected readonly string|AbstractModel $model,
        protected readonly StorageContextInterface $storageContext,
        protected readonly FactoryProviderInterface $factoryProvider
    )
    {
        $entity = $this->model::getEntityName();
        $this->query = Sql::select('*')->from($entity);
    }

    public function getQuery(): SelectiveQuery
    {
        return $this->query;
    }

    public function load(bool $reload = false): array
    {
        if(!$this->loaded || $reload){
            $this->items = $this->_loadDbData();
        }
        return $this->items;
    }

    protected function _loadDbData(): array
    {
        $dbResult = $this->storageContext->query($this->query);
        $result = [];
        foreach ($dbResult->fetchAll() as $dbDataRow) {
            $data = $dbDataRow[$this->model::getEntityName()];
            $modelInstanceFactory = $this->getModelInstance($this->model);
            /** @var AbstractModel $modelInstance */
            $modelInstance = $modelInstanceFactory->create();
            $modelInstance->__load($data);
            $result[] = $modelInstance;
        }
        return $result;
    }

    /**
     * @param string $model
     * @return object|null
     */
    protected function getModelInstance(string $model): ?object
    {
        return $this->factoryProvider->getFactory($model);
    }

}