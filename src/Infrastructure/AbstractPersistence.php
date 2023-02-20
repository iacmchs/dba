<?php

namespace App\Infrastructure;

abstract class AbstractPersistence implements PersistentInterface
{
    protected array $connectionParams;

    public function __construct(array $connectionParams)
    {
        $this->connectionParams = $connectionParams;
    }

    private function setConnectionParams($params): void
    {
        $this->connectionParams = $params;
    }
}
