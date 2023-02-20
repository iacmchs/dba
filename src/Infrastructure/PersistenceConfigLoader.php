<?php

namespace App\Infrastructure;

use Symfony\Component\Yaml\Yaml;

/**
 * Drivers loader persistence connection
 */
class PersistenceConfigLoader
{
    public function __construct(private readonly string $direction, private readonly string $config)
    {
    }

    public function load()
    {
        $parseFile = Yaml::parseFile($this->direction . $this->config);

        return $parseFile['persistence'];
    }
}
