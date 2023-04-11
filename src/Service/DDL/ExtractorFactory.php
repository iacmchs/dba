<?php

declare(strict_types=1);

namespace App\Service\DDL;

use App\Anonymization\AnonymizerInterface;
use App\Configuration\ConfigurationManagerInterface;
use App\Exception\Service\DDL\DataExtractorNotFoundException;
use App\Exception\Service\DDL\InvalidExtractorInterfaceException;
use App\Exception\Service\DDL\StructureExtractorNotFoundException;
use App\Service\DbConnectionSetterInterface;
use App\Service\DDL\Extractor\DbStructureExtractorInterface;
use Doctrine\DBAL\Connection;
use Traversable;

/**
 * ExtractorFactory create instance which can extract db metadata.
 * An extractor instance is created based on connection instance.
 */
class ExtractorFactory
{
    /**
     * Available extractors.
     */
    private array $extractors = [];

    /**
     * Create an extractor factory instance.
     *
     * @param Traversable $extractors
     */
    public function __construct(Traversable $extractors)
    {
        foreach ($extractors as $extractor) {
            if ($extractor instanceof DbStructureExtractorInterface) {
                $this->extractors[$extractor->getDbDriver()][DbStructureExtractorInterface::class] = $extractor;
                continue;
            }

            if ($extractor instanceof DbDataExtractorInterface) {
                $this->extractors[$extractor->getDbDriver()][DbDataExtractorInterface::class] = $extractor;
            }
        }
    }

    /**
     * Create db structure extractor based on db connection.
     *
     * @param Connection $connection
     *
     * @return DbStructureExtractorInterface
     *
     * @throws InvalidExtractorInterfaceException
     * @throws StructureExtractorNotFoundException
     */
    public function createStructureExtractor(Connection $connection): DbStructureExtractorInterface
    {
        if (!isset($this->extractors[$connection->getDriver()::class][DbStructureExtractorInterface::class])) {
            throw StructureExtractorNotFoundException::byDbDriverName($connection->getDriver()::class);
        }

        /** @var DbStructureExtractorInterface $extractor */
        $extractor = $this->extractors[$connection->getDriver()::class][DbStructureExtractorInterface::class];
        if (!$extractor instanceof DbConnectionSetterInterface) {
            throw InvalidExtractorInterfaceException::byInterface(DbConnectionSetterInterface::class);
        }

        $extractor->setDbConnection($connection);

        return $extractor;
    }

    /**
     * Create db data extractor based on db connection.
     *
     * @param \Doctrine\DBAL\Connection $connection
     * @param \App\Configuration\ConfigurationManagerInterface $configurationManager
     * @param \App\Anonymization\AnonymizerInterface $anonymizer
     *
     * @return \App\Service\DDL\DbDataExtractorInterface
     *
     * @throws \App\Exception\Service\DDL\DataExtractorNotFoundException
     * @throws \App\Exception\Service\DDL\InvalidExtractorInterfaceException
     */
    public function createDataExtractor(
        Connection $connection,
        ConfigurationManagerInterface $configurationManager,
        AnonymizerInterface $anonymizer
    ): DbDataExtractorInterface {
        if (!isset($this->extractors[$connection->getDriver()::class][DbDataExtractorInterface::class])) {
            throw DataExtractorNotFoundException::byDbDriverName($connection->getDriver()::class);
        }

        /** @var DbDataExtractorInterface $extractor */
        $extractor = $this->extractors[$connection->getDriver()::class][DbDataExtractorInterface::class];
        if (!$extractor instanceof DbConnectionSetterInterface) {
            throw InvalidExtractorInterfaceException::byInterface(DbConnectionSetterInterface::class);
        }

        $extractor->setDbConnection($connection);
        $extractor->setConfigurationManager($configurationManager);
        $extractor->setAnonymizer($anonymizer);

        return $extractor;
    }
}
