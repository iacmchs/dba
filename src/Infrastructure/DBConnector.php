<?php
declare(strict_types=1);

namespace App\Infrastructure;

use App\Tool\DsnParser;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;

class DBConnector
{
    public function __construct(private readonly DsnParser $dsnParser)
    {
    }

    /**
     * Create connection to DB
     * Example dsn : pgsql://user:password@127.0.0.1:5432/database
     *               pdo-pgsql://user:password@127.0.0.1:5432/database
     * @param string $dsn
     *
     * @return Connection
     * @throws Exception
     */
    public function create(string $dsn): Connection
    {
        $parsed = $this->dsnParser->parse($dsn);

        return DriverManager::getConnection($parsed);
    }
}
