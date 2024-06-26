<?php

declare(strict_types=1);

namespace App\Service\Extractor;

use App\Exception\Service\Extractor\ConnectionNotInjectedException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\PgSQL\Driver;
use Doctrine\DBAL\Exception;
use Symfony\Component\Process\Process;

/**
 * Implementation of the DbStructureExtractorInterface for a PostgresQL.
 */
class PostgresDbStructureExtractor implements
    DbStructureExtractorInterface,
    DbDriverNameInterface,
    DbConnectionSetterInterface
{
    /**
     * DB connection.
     *
     * @var Connection|null
     */
    private ?Connection $conn = null;

    /**
     * Create db structure extractor for Postgresql.
     *
     * @param string $pgDump
     */
    public function __construct(private readonly string $pgDump)
    {
    }

    /**
     * @inheritDoc
     */
    public function getDbDriver(): string
    {
        return Driver::class;
    }

    /**
     * @inheritDoc
     */
    public function setDbConnection(Connection $connection): void
    {
        $this->conn = $connection;
    }

    /**
     * @inheritDoc
     *
     * @return void
     *
     * @throws ConnectionNotInjectedException
     * @throws Exception
     */
    public function dumpStructure(string $path): void
    {
        $database = $this->getConnection()->getDatabase();
        $params = $this->getConnection()->getParams();
        $command = [
            $this->pgDump,
            $database,
        ];

        if (!empty($params['user'])) {
            $command[] = '-U ' . $params['user'];
        }
        if (!empty($params['host'])) {
            $command[] = '-h ' . $params['host'];
        }
        if (!empty($params['port'])) {
            $command[] = '-p ' . $params['port'];
        }

        $command[] = '-s';
        $exportFileName = $this->getNewStructureFileName($database);
        $command = implode(' ', $command);
        $command .= ' > ' . $path . '/' . $exportFileName;

        Process::fromShellCommandline($command)->run();
    }

    /**
     * Return db connection.
     *
     * @return Connection
     *
     * @throws ConnectionNotInjectedException
     */
    private function getConnection(): Connection
    {
        if ($this->conn === null) {
            throw ConnectionNotInjectedException::create();
        }

        return $this->conn;
    }

    /**
     * Get new structure file name.
     *
     * @param string $name
     *
     * @return string
     */
    private function getNewStructureFileName(string $name): string
    {
        return '00_' . $name . '_structure.sql';
    }
}
