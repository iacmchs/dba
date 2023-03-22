<?php

namespace App\Model\DDL;

use Symfony\Component\Serializer\Annotation\SerializedName;

readonly class IndexStructure implements DdlQueryPartInterface
{
    public function __construct(
        #[SerializedName('tablename')]
        private string $tableName,
        #[SerializedName('indexname')]
        private string $name,
        #[SerializedName('indexdef')]
        private string $ddl
    )
    {
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDdl(): string
    {
        return $this->ddl;
    }

    /**
     * @inheritDoc
     */
    public function toDDL(): string
    {
        return $this->ddl;
    }
}
