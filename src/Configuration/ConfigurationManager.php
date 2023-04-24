<?php

declare(strict_types=1);

namespace App\Configuration;

use App\Exception\ConfigFileNotFoundException;
use Symfony\Component\Yaml\Yaml;

class ConfigurationManager implements ConfigurationManagerInterface
{

    /**
     * Project config (database dump config).
     *
     * @var array
     */
    private array $config;

    public function __construct(string $filePath = '')
    {
        if ($filePath) {
            $this->load($filePath);
        }
    }

    /**
     * @inheritDoc
     */
    public function load(string $filePath): void
    {
        $config = Yaml::parse(file_get_contents($filePath));
        if (!$config) {
            throw new ConfigFileNotFoundException($filePath);
        }

        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function getTables(): array
    {
        return $this->config['database']['tables'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getEntities(): array
    {
        return $this->config['database']['entities'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getAnonymization(): array
    {
        return $this->config['database']['anonymization'] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getTableConfig(string $tableName): array
    {
        $configTables = $this->getTables();
        $config = [];

        foreach ($configTables as $configTableKey => $configTableValue) {
            if ($configTableKey === $tableName) {
                $config = is_numeric($configTableValue)
                    ? ['get' => (float) $configTableValue]
                    : $configTableValue;
            } elseif (!empty($configTableValue['table']) && $configTableValue['table'] === $tableName) {
                $config = $configTableValue;
            } elseif (!empty($configTableValue['table_regex']) && preg_match($configTableValue['table_regex'], $tableName)) {
                $config = $configTableValue;
            }

            if ($config) {
                break;
            }
        }

        $config += [
            'get' => 1,
            'table' => $tableName,
            'table_regex' => '',
            'where' => [],
            'export_method' => '',
        ];

        return $config;
    }

    /**
     * @inheritDoc
     */
    public function getEntityConfig(string $entityName): array
    {
        $configEntities = $this->getEntities();
        $config = $configEntities[$entityName] ?? [];

        if ($config) {
            $config += [
                'get' => 0.01,
                'table' => $entityName,
                'where' => [],
                'relations' => [],
                'export_method' => '',
            ];
        }

        return $config;
    }

    /**
     * @inheritDoc
     */
    public function getTableAnonymization(string $tableName): array
    {
        $res = [];
        $anonymizationRules = $this->getAnonymization();

        // Go through all anonymization rules and find ones for specified table.
        foreach ($anonymizationRules as $curTableName => $anonymizationRule) {
            $matches = false;

            if ($curTableName === $tableName) {
                $matches = true;
            } elseif (($anonymizationRule['table'] ?? '') === $tableName) {
                $matches = true;
            } elseif (($anonymizationRule['table_regex'] ?? '') && preg_match($anonymizationRule['table_regex'], $tableName)) {
                $matches = true;
            }

            if ($matches) {
                $res[$curTableName] = $anonymizationRule + [
                    'table' => $curTableName,
                    'table_regex' => '',
                    'where' => [],
                    'fields' => [],
                ];
            }
        }

        return $res;
    }

    /**
     * @inheritDoc
     */
    public function getTablePercentage(string $tableName, array $tableConfig = []): float
    {
        if (!$tableConfig) {
            $tableConfig = $this->getTableConfig($tableName);
        }

        return $tableConfig['get'] ?? 1;
    }

    /**
     * @inheritDoc
     */
    public function canTableBeDumped(string $tableName, array $tableConfig = []): bool
    {
        return (bool) $this->getTablePercentage($tableName, $tableConfig);
    }
}
