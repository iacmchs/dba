<?php

/**
 * @file An implementation of a table structure
 */

declare(strict_types=1);

namespace App\Model\DDL;

readonly class TableStructure implements DdlQueryPartInterface
{
    /**
     * Get instance of a TableStructure
     *
     * @param string $name
     * @param array $fields
     */
    public function __construct(
        private string $name,
        /**
         * Table's fields
         *
         * @var DdlQueryPartInterface[]
         */
        private array  $fields
    )
    {
    }

    /**
     * Return table name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Return table's fields
     *
     * @return DdlQueryPartInterface[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @inheritDoc
     */
    public function toDDL(): string
    {
        return sprintf(
            "CREATE TABLE $this->name %s(%s%s%s);",
            PHP_EOL,
            PHP_EOL,
            implode(
                ', ' . PHP_EOL,
                array_map(
                    fn(DdlQueryPartInterface $f): string => "     " . $f->toDDL(),
                    $this->fields
                )
            ),
            PHP_EOL
        );
    }
}
