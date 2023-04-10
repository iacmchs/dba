<?php

declare(strict_types=1);

namespace App\Anonymization;

interface AnonymizerInterface
{

    /**
     * Anonymizes data for a row from some db table.
     *
     * @param string $tableName
     *   DB table name.
     * @param array $row
     *   A db table row (with data).
     * @param array $tableAnonymization
     *   An array of anonymization rules that can be applied to the table data.
     *
     * @return array
     *   A db table row with anonymization rules applied, if any.
     */
    public function anonymize(string $tableName, array $row, array $tableAnonymization = []): array;
}
