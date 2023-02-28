<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Exception\DsnNotValidException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Tools\DsnParser;

class DBConnector
{
    public function __construct(private readonly DsnParser $dsnParser)
    {
    }

    /**
     * Creates a DB connection.
     *
     * @param string $dsn
     *    DSN credentials. Examples:
     *        pgsql://user:password@127.0.0.1:5432/database
     *        pdo-pgsql://user:password@127.0.0.1:5432/database
     *
     * @return Connection
     *    DBAL Connection.
     *
     * @throws Exception
     *    Different DBAL exceptions.
     *
     * @throws DsnNotValidException
     *    Invalid DSN exception.
     */
    public function create(string $dsn): Connection
    {
        $parsed = $this->dsnParser->parse($dsn);
        if (!$parsed) {
            throw new DsnNotValidException($dsn);
        }

        return DriverManager::getConnection($parsed);
    }
}
