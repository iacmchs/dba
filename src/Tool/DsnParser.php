<?php

namespace App\Tool;

class DsnParser
{
    public function parse(string $dsnUrl): array
    {
        return (new \Doctrine\DBAL\Tools\DsnParser())->parse($dsnUrl);
    }
}
