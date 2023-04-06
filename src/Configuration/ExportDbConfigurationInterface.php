<?php

namespace App\Configuration;

interface ExportDbConfigurationInterface
{
    public function getTables(string $database): array;
}
