<?php

declare(strict_types=1);

namespace App\Model\DDL;

class TableStructure
{
    private string $name;

    /**
     * @var FieldStructure[]
     */
    private array $fields;

    /**
     * @var ConstraintStructure[]
     */
    private array $constraints;

    /**
     * @param FieldStructure[] $fields
     * @param ConstraintStructure[] $constraints
     */
    public function __construct(string $name, array $fields, array $constraints)
    {
        $this->name = $name;
        $this->fields = $fields;
        $this->constraints = $constraints;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }
}
