<?php

namespace App\Configuration;

use Symfony\Component\Yaml\Yaml;

class ExportDbConfiguration
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

    public function getTables(string $database): array
    {
        return $this->config['databases'][$database]['tables'];
    }
}
