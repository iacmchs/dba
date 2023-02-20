<?php

declare(strict_types=1);

namespace App\Model\DDL;

use Symfony\Component\Serializer\Annotation\SerializedName;

class ConstraintStructure
{
    #[SerializedName('constraint_name')]
    private string $name;

    #[SerializedName('constraint_type')]
    private string $type;

    #[SerializedName('column_name')]
    private string $columnName;

    #[SerializedName('referenced_table_name')]
    private string $referencedTable;

    #[SerializedName('referenced_column_name')]
    private string $referencedColumn;

    #[SerializedName('update_rule')]
    private ?string $updateRule;

    #[SerializedName('delete_rule')]
    private ?string $deleteRule;

    public function __construct(
        string  $name,
        string  $type,
        string  $columnName,
        string  $referencedTable,
        string  $referencedColumn,
        ?string $updateRule,
        ?string $deleteRule
    ){
        $this->name = $name;
        $this->type = $type;
        $this->columnName = $columnName;
        $this->referencedTable = $referencedTable;
        $this->referencedColumn = $referencedColumn;
        $this->updateRule = $updateRule;
        $this->deleteRule = $deleteRule;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    public function getReferencedTable(): string
    {
        return $this->referencedTable;
    }

    public function getReferencedColumn(): string
    {
        return $this->referencedColumn;
    }

    public function getUpdateRule(): ?string
    {
        return $this->updateRule;
    }

    public function getDeleteRule(): ?string
    {
        return $this->deleteRule;
    }
}
