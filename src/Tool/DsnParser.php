<?php
declare(strict_types=1);

namespace App\Tool;

class DsnParser
{
    /**
     * Parse dsn url to array
     *
     * @param string $dsnUrl
     *
     * @return array
     */
    public function parse(string $dsnUrl): array
    {
        return (new \Doctrine\DBAL\Tools\DsnParser())->parse($dsnUrl);
    }
}
