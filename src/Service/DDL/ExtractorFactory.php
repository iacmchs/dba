<?php

/**
 * @file
 * ExtractorFactory create instance which can extract db metadata.
 *
 * An extractor instance is created based on connection instance.
 */

declare(strict_types=1);

namespace App\Service\DDL;

use App\Exception\Service\DDL\InvalidStructureExtractorInterface;
use App\Exception\Service\DDL\StructureExtractorNotFound;
use App\Service\DbConnectionSetterInterface;
use App\Service\DDL\Extractor\DbStructureExtractorInterface;
use PDO;
use Traversable;

class ExtractorFactory
{
    /**
     * Available extractors.
     *
     * @var array<array-key, DbStructureExtractorInterface>
     */
    private array $extractors = [];

    /**
     * Create an extractor factory instance.
     *
     * @param Traversable $extractors
     * @throws InvalidStructureExtractorInterface
     */
    public function __construct(Traversable $extractors)
    {
        foreach ($extractors as $extractor) {
            if (!$extractor instanceof DbStructureExtractorInterface) {
                throw InvalidStructureExtractorInterface::byInterface(DbStructureExtractorInterface::class);
            }

            /** @psalm-suppress InvalidPropertyAssignmentValue,UndefinedInterfaceMethod */
            $this->extractors[$extractor->getDbDriverName()] = $extractor;
        }
    }

    /**
     * Create db structure extractor based on db connection.
     *
     * @param PDO $connection
     * @return DbStructureExtractorInterface
     * @throws StructureExtractorNotFound
     * @throws InvalidStructureExtractorInterface
     */
    public function createExtractor(PDO $connection): DbStructureExtractorInterface
    {
        /** @var string $driverName */
        $driverName = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        if (!isset($this->extractors[$driverName])) {
            throw StructureExtractorNotFound::byDBDriverName($driverName);
        }

        $extractor = $this->extractors[$driverName];
        if (!$extractor instanceof DbConnectionSetterInterface) {
            throw InvalidStructureExtractorInterface::byInterface(DbConnectionSetterInterface::class);
        }

        $extractor->setDbConnection($connection);

        return $extractor;
    }
}
