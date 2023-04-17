<?php

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

use App\Configuration\ConfigurationManagerInterface;

/**
 * Db data configuration setter interface.
 */
interface ConfigurationManagerSetterInterface
{
    /**
     * Set configuration manager for data extractor.
     *
     * @param ConfigurationManagerInterface $configurationManager
     *   Configuration manager.
     *
     * @return void
     */
    public function setConfigurationManager(ConfigurationManagerInterface $configurationManager): void;
}
