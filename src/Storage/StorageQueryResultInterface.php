<?php

namespace Siarko\DbModelApi\Storage;

use JetBrains\PhpStorm\ArrayShape;

interface StorageQueryResultInterface
{

    public function count(): int;

    public function fetchAll(): array;

    public function getError(): ?array;

    public function getNativeObject(): mixed;

}