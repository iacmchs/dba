<?php

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

use App\Configuration\ExportDbConfiguration;

/**
 * Db data configuration setter interface.
 */
interface DbDataConfigurationSetterInterface
{
    /**
     * Set configuration for data extractor.
     *
     * @param ExportDbConfiguration $configuration
     *
     * @return void
     */
    public function setConfiguration(ExportDbConfiguration $configuration): void;
}
