<?php

namespace Siarko\DbModelApi\Schema;

use Siarko\SqlCreator\DatabaseElement\Table;

interface StorageInterface
{
    public function getSchema(): array;

    public function setSchema(array $schema, bool $updateDatabase = true);

    public function getEntity(string $entity): ?Table;

    public function entityExists(string $entity): bool;

}