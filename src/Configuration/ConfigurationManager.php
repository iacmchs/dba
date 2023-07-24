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
        $this->initOptions();
        $this->copyTablesFromEntities();
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
     * @inheritdoc
     */
    public function getOptions(string $sectionName = ''): array
    {
        return $sectionName ? $this->config['options'][$sectionName] : $this->config['options'];
    }

    /**
     * @inheritDoc
     */
    public function getTableConfig(string $tableName, bool $strict = false): array
    {
        $configTables = $this->getTables();
        $config = [];
        $enrichConfigName = '';

        if (!empty($configTables[$tableName]['_is_enriched'])) {
            return $configTables[$tableName];
        }

        if ($strict) {
            $config = $configTables[$tableName] ?? [];

            return is_numeric($config)
                ? ['get' => (float) $config]
                : $config;
        }

        foreach ($configTables as $configTableKey => $configTableValue) {
            if ($configTableKey === $tableName) {
                $config = is_numeric($configTableValue)
                    ? ['get' => (float) $configTableValue]
                    : $configTableValue;
                $enrichConfigName = $configTableKey;
            } elseif (!empty($configTableValue['table']) && $configTableValue['table'] === $tableName) {
                $config = $configTableValue;
                $enrichConfigName = $configTableKey;
            } elseif (!empty($configTableValue['table_regex']) && preg_match($configTableValue['table_regex'], $tableName)) {
                $config = $configTableValue;
            }

            if ($config) {
                break;
            }
        }

        $config += [
            'get' => $this->getOption('tables', 'get'),
            'table' => $tableName,
            'table_regex' => '',
            'where' => [],
            'export_method' => $this->getOption('tables', 'export_method'),
        ];

        // Save enriched config back to original table config array to
        // optimize performance for the next table config search.
        if ($enrichConfigName) {
            $config['_is_enriched'] = true;
            $this->setTableConfig($enrichConfigName, $config);
        }

        return $config;
    }

    /**
     * @inheritDoc
     */
    public function getEntityConfig(string $entityName): array
    {
        $configEntities = $this->getEntities();
        $config = $configEntities[$entityName] ?? [];

        if ($config && empty($config['_is_enriched'])) {
            $config += [
                'get' => $this->getOption('entities', 'get'),
                'table' => $entityName,
                'where' => [],
                'relations' => [],
                'fields' => [],
                'export_method' => $this->getOption('entities', 'export_method'),
                '_is_enriched' => true,
            ];
            $this->setEntityConfig($entityName, $config);
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

        return $tableConfig['get'] ?? $this->getOption('tables', 'get');
    }

    /**
     * @inheritDoc
     */
    public function canTableBeDumped(string $tableName, array $tableConfig = []): bool
    {
        return (bool) $this->getTablePercentage($tableName, $tableConfig);
    }

    /**
     * @inheritdoc
     */
    public function getOption(string $sectionName, string $optionName): mixed
    {
        return $this->config['options'][$sectionName][$optionName] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function shouldSkip(string $sectionName): bool
    {
        return (bool) ($this->getOption($sectionName, 'should_skip') ?? false);
    }

    /**
     * Sets default values to options that are not specified in config file.
     *
     * @return void
     */
    private function initOptions(): void
    {
        $this->config['options']['structure'] = ($this->config['options']['structure'] ?? []) + [
                'should_skip' => 0,
            ];
        $this->config['options']['tables'] = ($this->config['options']['tables'] ?? []) + [
                'should_skip' => 0,
                'get' => 1,
                'insert_rows_max' => 300,
                'export_method' => 'default',
            ];
        $this->config['options']['entities'] = ($this->config['options']['entities'] ?? []) + [
                'should_skip' => 0,
                'get' => 0.01,
                'insert_rows_max' => 300,
                'export_method' => 'default',
            ];
        $this->config['options']['anonymization'] = ($this->config['options']['anonymization'] ?? []) + [
                'should_skip' => 0,
                'faker_locale' => 'en_US',
            ];
    }

    /**
     * Update database.tables list with tables from database.entities section.
     *
     * Here we copy tables from entities (and their relations) to the
     * database.tables section of config and set get=0 to them to avoid
     * direct exporting data from these tables.
     * If we need a full dump of data from some table then we should
     * exclude it from relations (and maybe add to database.tables section
     * with get=1).
     *
     * @param array $list
     *   List of entities or relations with their config.
     *   If not set then we get all entities from config file.
     *
     * @return void
     */
    private function copyTablesFromEntities(array $list = []): void
    {
        if (empty($list)) {
            $list = $this->getEntities();
        }

        foreach ($list as $entityName => $entityConfig) {
            $entityConfig['table'] = $entityConfig['table'] ?? $entityName;
            if (empty($this->getTableConfig($entityConfig['table'], true))) {
                $this->setTableConfig($entityConfig['table'], ['get' => 0]);
            }

            if (!empty($entityConfig['relations'])) {
                $this->copyTablesFromEntities($entityConfig['relations']);
            }
        }
    }

    /**
     * Updates table config.
     *
     * @param string $tableName
     *   DB table name.
     * @param array $tableConfig
     *   New table dump config.
     *
     * @return void
     */
    private function setTableConfig(string $tableName, array $tableConfig): void
    {
        $this->config['database']['tables'][$tableName] = $tableConfig;
    }

    /**
     * Updates entity config.
     *
     * @param string $entityName
     *   Entity name.
     * @param array $entityConfig
     *   New entity dump config.
     *
     * @return void
     */
    private function setEntityConfig(string $entityName, array $entityConfig): void
    {
        $this->config['database']['entities'][$entityName] = $entityConfig;
    }
}
