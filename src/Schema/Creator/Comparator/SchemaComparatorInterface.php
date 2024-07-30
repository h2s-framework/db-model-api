<?php

namespace Siarko\DbModelApi\Schema\Creator\Comparator;

interface SchemaComparatorInterface
{

    public const KEY_CREATE = 'CREATE';
    public const KEY_MODIFY = 'MODIFY';
    public const KEY_DROP = 'DROP';

    public function compare(array $mainSchema, array $secondarySchema): array;

}