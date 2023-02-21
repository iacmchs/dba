<?php

declare(strict_types=1);

namespace App\Model\DDL;

use Symfony\Component\Serializer\Annotation\SerializedName;

class FieldStructure implements DDLQueryPartInterface
{
    #[SerializedName('column_name')]
    private string $name;

    #[SerializedName('data_type')]
    private string $type;

    #[SerializedName('is_nullable')]
    private string $isNull;

    #[SerializedName('column_default')]
    private ?string $default;

    #[SerializedName('character_maximum_length')]
    private ?int $length;

    public function __construct(
        string  $name,
        string  $type,
        string  $isNull,
        ?string $default,
        ?int    $length,
    )
    {
        $this->name = $name;
        $this->type = $type;
        $this->isNull = $isNull;
        $this->default = $default;
        $this->length = $length;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIsNull(): string
    {
        return $this->isNull;
    }

    public function getDefault(): ?string
    {
        return $this->default;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function toDDL(): string
    {
        $query = "$this->name";
        $query .= " $this->type";

        if ($this->length) {
            $query .= "($this->length)";
        }

        if ($this->isNull === 'NO') {
            $query .= " NOT NULL";
        }

        if ($this->default) {
            $query .= " DEFAULT $this->default";
        }

        return $query;
    }
}
