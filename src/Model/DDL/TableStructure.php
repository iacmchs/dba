<?php

declare(strict_types=1);

namespace App\Model\DDL;

class TableStructure implements DDLQueryPartInterface
{
    private string $name;

    /**
     * @var DDLQueryPartInterface[]
     */
    private array $fields;

    /**
     * @param DDLQueryPartInterface[] $fields
     */
    public function __construct(string $name, array $fields)
    {
        $this->name = $name;
        $this->fields = $fields;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return DDLQueryPartInterface[]
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
            "CREATE TABLE $this->name \n(\n%s\n);",
            implode(
                ",\n",
                array_map(
                    fn(DDLQueryPartInterface $f): string => "     " . $f->toDDL(),
                    $this->fields
                )
            )
        );
    }
}
