<?php

namespace App\Configuration;

interface ExportDbConfigurationInterface
{
    public function getTables(): array;
}
