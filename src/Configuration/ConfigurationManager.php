<?php

namespace App\Configuration;

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
        $this->config = Yaml::parse(file_get_contents($filePath));
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
    public function getTableConfig(string $tableName): array
    {
        $configTables = $this->getTables();
        $config = [];

        foreach ($configTables as $configTableKey => $configTableValue) {
            if ($configTableKey == $tableName) {
                $config = is_numeric($configTableValue)
                    ? ['get' => (float) $configTableValue]
                    : $configTableValue;
            }
            elseif (!empty($configTableValue['table']) && $configTableValue['table'] === $tableName) {
                $config = $configTableValue;
            }
            elseif (!empty($configTableValue['table_regex']) && preg_match($configTableValue['table_regex'], $tableName)) {
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
