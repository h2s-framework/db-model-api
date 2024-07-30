<?php

namespace Siarko\DbModelApi\Schema;

use Siarko\DependencyManager\DependencyManager;

class StorageInterfaceProxy
{

    public function __construct(
        protected readonly DependencyManager $dependencyManager
    )
    {
    }

    public function get(): StorageInterface
    {
        return $this->dependencyManager->get(StorageInterface::class);
    }

}