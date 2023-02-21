<?php

declare(strict_types=1);

namespace App\Service\DDL\Extractor;

interface DBDriverNameInterface
{
    /**
     * Gets name of the db driver that an instance uses to connect to db
     *
     * @return string
     */
    public function getDBDriverName(): string;
}
