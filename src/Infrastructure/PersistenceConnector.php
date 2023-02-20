<?php

namespace App\Infrastructure;

use App\Tool\DsnParser;
use RuntimeException;

class PersistenceConnector
{
    public function __construct(
        private readonly PersistenceConfigLoader $configLoader,
        private readonly DsnParser $dsnParser
    )
    {
    }

    public function create(string $dsn): PersistentInterface
    {
        $config = $this->configLoader->load();

        $parsed = $this->dsnParser->parse($dsn);
        $driverName = $parsed['driver'];

        if (!isset($config[$driverName])) {
            throw new RuntimeException('No driver ' . $driverName);
        }

        return new($config[$driverName])($parsed);
    }
}
