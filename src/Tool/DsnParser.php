<?php

declare(strict_types=1);

namespace App\Tool;

class DsnParser
{
    public function __construct(private readonly \Doctrine\DBAL\Tools\DsnParser $dsnParser)
    {
    }

    /**
     * Parse dsn url to array
     *
     * @param string $dsnUrl
     *
     * @return array
     */
    public function parse(string $dsnUrl): array
    {
        return $this->dsnParser->parse($dsnUrl);
    }
}
