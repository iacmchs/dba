<?php

/**
 * @file
 * An implementation of a table's field structure.
 */

declare(strict_types=1);

namespace App\Model\DDL;

use Symfony\Component\Serializer\Annotation\SerializedName;

readonly class FieldStructure implements DdlQueryPartInterface
{
    /**
     * Get instance of FieldStructure.
     *
     * @param string $name
     * @param string $type
     * @param string $isNull
     * @param string|null $default
     * @param int|null $length
     */
    public function __construct(
        #[SerializedName('column_name')]
        private string  $name,
        #[SerializedName('data_type')]
        private string  $type,
        #[SerializedName('is_nullable')]
        private string  $isNull,
        #[SerializedName('column_default')]
        private ?string $default,
        #[SerializedName('character_maximum_length')]
        private ?int    $length,
    )
    {
    }

    /**
     * Return name of field.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Return type of field.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Return 'YES' if fields can be nullable and 'NO' otherwise.
     *
     * @return string
     */
    public function getIsNull(): string
    {
        return $this->isNull;
    }

    /**
     * Return field's default value.
     *
     * @return string|null
     */
    public function getDefault(): ?string
    {
        return $this->default;
    }

    /**
     * Return length of field's value.
     *
     * @return int|null
     */
    public function getLength(): ?int
    {
        return $this->length;
    }

    /**
     * @inheritDoc
     */
    public function toDDL(): string
    {
        $query = "$this->name $this->type";

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
