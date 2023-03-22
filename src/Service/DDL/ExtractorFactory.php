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
use Doctrine\DBAL\Connection;
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
            $this->extractors[$extractor->getDbDriver()] = $extractor;
        }
    }

    /**
     * Create db structure extractor based on db connection.
     *
     * @param Connection $connection
     *
     * @return DbStructureExtractorInterface
     * @throws InvalidStructureExtractorInterface
     * @throws StructureExtractorNotFound
     */
    public function createExtractor(Connection $connection): DbStructureExtractorInterface
    {
        if (!isset($this->extractors[$connection->getDriver()::class])) {
            throw StructureExtractorNotFound::byDBDriverName($connection->getDriver()::class);
        }

        $extractor = $this->extractors[$connection->getDriver()::class];
        if (!$extractor instanceof DbConnectionSetterInterface) {
            throw InvalidStructureExtractorInterface::byInterface(DbConnectionSetterInterface::class);
        }

        $extractor->setDbConnection($connection);

        return $extractor;
    }
}
