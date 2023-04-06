<?php

namespace App\Configuration;

use Symfony\Component\Yaml\Yaml;

class ExportDbConfiguration implements ExportDbConfigurationInterface
{
    private array $config;

    public function __construct(string $file)
    {
        $this->load($file);
    }

    private function load(string $file): void
    {
        $this->config = Yaml::parse(file_get_contents($file));
    }

    public function getTables(): array
    {
        return $this->config['database']['tables'] ?? [];
    }
}
