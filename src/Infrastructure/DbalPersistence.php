<?php

namespace App\Infrastructure;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;

final class DbalPersistence extends AbstractPersistence
{
    private Connection $connection;

    /**
     * @return object
     * @throws Exception
     */
    public function connect(): object
    {
        $connection = DriverManager::getConnection($this->connectionParams);
        $this->connection = $connection;
        $this->connection->connect();

        return $this->connection;
    }

    public function close(): bool
    {
        $this->connection->close();

        return true;
    }

    public function isConnected(): bool
    {
        return $this->connection->isConnected();
    }
}
