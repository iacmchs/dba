<?php

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

use App\Configuration\ConfigurationManagerInterface;
use App\Exception\Service\DDL\Extractor\ConfigurationManagerNotInjected;
use App\Exception\Service\DDL\Extractor\ConnectionNotInjected;
use App\Service\DbConnectionSetterInterface;
use App\Service\DDL\DbDataExtractorInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDO\PgSQL\Driver;
use Symfony\Component\Filesystem\Filesystem;

/**
 * PostgreSQL data extractor.
 */
class PostgresDataExtractor implements
    DbDataExtractorInterface,
    DbDriverNameInterface,
    DbConnectionSetterInterface,
    ConfigurationManagerSetterInterface
{
    /**
     * DB connection.
     *
     * @var Connection|null
     */
    private ?Connection $connection;

    private ?ConfigurationManagerInterface $configurationManager;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(private readonly Filesystem $filesystem)
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
     * Returns db connection.
     *
     * @return Connection
     *
     * @throws ConnectionNotInjected
     */
    private function getConnection(): Connection
    {
        if (!$this->connection) {
            throw ConnectionNotInjected::create();
        }

        return $this->connection;
    }

    /**
     * @inheritDoc
     */
    public function setDbConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Returns configuration manager.
     *
     * @return ConfigurationManagerInterface
     *
     * @throws ConfigurationManagerNotInjected
     */
    private function getConfigurationManager(): ConfigurationManagerInterface
    {
        if (!$this->configurationManager) {
            throw ConfigurationManagerNotInjected::create();
        }

        return $this->configurationManager;
    }

    /**
     * @inheritDoc
     */
    public function setConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @inheritDoc
     */
    public function dumpTable(string $tableName, string $dir, array $tableConfig = [], string $fileNamePrefix = '10'): void
    {
        $dir = $dir . '/' . $this->getNewTableFileName($tableName, $fileNamePrefix);
        $sql = "INSERT INTO $tableName VALUES" . PHP_EOL;
        $where = '';

        $percent = $this->getConfigurationManager()->getTablePercentage($tableName, $tableConfig);
        if ($percent && $percent < 1) {
            $where = " WHERE RANDOM() < $percent";
        }

        $requestTable = "SELECT * FROM $tableName".$where;
        $haveResult = false;
        foreach ($this->getConnection()->iterateNumeric($requestTable) as $row) {
            $haveResult = true;
            // Export previous row to file.
            $this->filesystem->appendToFile($dir, $sql);
            $sql = '';

            // Prepare current row for export.
            foreach ($row as $key => $item) {
                if (null === $item) {
                    $sql .= 'null';
                } elseif (is_bool($item)) {
                    $sql .= $item ? 'true' : 'false';
                } elseif (is_numeric($item)) {
                    $sql .= $item;
                } elseif (is_resource($item)) {
                    $item = stream_get_contents($item);
                    $sql .= "'" . pg_escape_bytea($item) . "'";
                } else {
                    $sql .= "'" . pg_escape_string((string) $item) . "'";
                }

                if (array_key_last($row) !== $key) {
                    $sql .= ',';
                }
            }

            $sql = "($sql)," . PHP_EOL;
        }

        // Export last row to file.
        if ($haveResult) {
            $sql = rtrim(rtrim($sql), ',') . ';';
            $this->filesystem->appendToFile($dir, $sql);
        }
    }

    /**
     * @inheritDoc
     */
    public function canTableBeDumped(string $tableName, array $tableConfig = []): bool
    {
        return $this->getConfigurationManager()->canTableBeDumped($tableName, $tableConfig);
    }

    /**
     * Get new Table file name.
     *
     * @param string $name
     * @param string $prefix
     *
     * @return string
     */
    private function getNewTableFileName(string $name, ?string $prefix = ''): string
    {
        $name = str_replace('"', '', $name);

        return ($prefix ? $prefix.'_' : '').$name.'.sql';
    }
}
