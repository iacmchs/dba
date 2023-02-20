<?php

declare(strict_types=1);

namespace App\Service\DDL;

use App\Exception\Service\DDL\InvalidStructureExtractorInterface;
use App\Exception\Service\DDL\StructureExtractorNotFound;
use App\Service\DBConnectionSetterInterface;
use App\Service\DDL\Extractor\DBStructureExtractorInterface;
use PDO;
use Traversable;

class ExtractorFactory
{
    /**
     * @var array<array-key, DBStructureExtractorInterface>
     */
    private array $extractors = [];

    /**
     * @param Traversable $extractors
     * @throws InvalidStructureExtractorInterface
     */
    public function __construct(Traversable $extractors)
    {
        foreach ($extractors as $extractor) {
            if (!$extractor instanceof DBStructureExtractorInterface) {
                throw InvalidStructureExtractorInterface::byInterface(DBStructureExtractorInterface::class);
            }

            /** @psalm-suppress InvalidPropertyAssignmentValue,UndefinedInterfaceMethod */
            $this->extractors[$extractor->getDBDriverName()] = $extractor;
        }
    }

    /**
     * @param PDO $connection
     * @return DBStructureExtractorInterface
     * @throws StructureExtractorNotFound
     * @throws InvalidStructureExtractorInterface
     */
    public function createExtractor(PDO $connection): DBStructureExtractorInterface
    {
        /** @var string $driverName */
        $driverName = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (!isset($this->extractors[$driverName])) {
            throw StructureExtractorNotFound::byDBDriverName($driverName);
        }

        $extractor = $this->extractors[$driverName];
        if (!$extractor instanceof DBConnectionSetterInterface) {
            throw InvalidStructureExtractorInterface::byInterface(DBConnectionSetterInterface::class);
        }

        $extractor->setDBConnection($connection);

        return $extractor;
    }
}
