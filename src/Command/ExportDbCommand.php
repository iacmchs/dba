<?php

declare(strict_types=1);

namespace App\Command;

use App\Configuration\ConfigurationManager;
use App\Configuration\ConfigurationManagerInterface;
use App\Exception\DsnNotValidException;
use App\Exception\Service\Extractor\DataExtractorNotFoundException;
use App\Exception\Service\Extractor\InvalidExtractorInterfaceException;
use App\Exception\Service\Extractor\StructureExtractorNotFoundException;
use App\Infrastructure\DBConnector;
use App\Service\Anonymization\Anonymizer;
use App\Service\Anonymization\AnonymizerInterface;
use App\Service\Extractor\DbDataExtractorInterface;
use App\Service\Extractor\ExtractorFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use PDOException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Export DB command.
 */
#[AsCommand(
    name: 'app:db-export',
    description: 'Create DB dump to folder <DATABASE_DUMP_FOLDER>/<database-name_Ymd_His>. See DATABASE_DUMP_FOLDER in ENV file.'
)]
class ExportDbCommand extends Command
{

    /**
     * A database connection.
     *
     * @var \Doctrine\DBAL\Connection
     */
    private Connection $connection;

    /**
     * The db export configuration.
     *
     * @var \App\Configuration\ConfigurationManagerInterface
     */
    private ConfigurationManagerInterface $configurationManager;

    /**
     * Data anonymizer.
     *
     * @var \App\Service\Anonymization\AnonymizerInterface
     */
    private AnonymizerInterface $anonymizer;

    /**
     * Exports data from db.
     *
     * @var \App\Service\Extractor\DbDataExtractorInterface
     */
    private DbDataExtractorInterface $dataExtractor;

    /**
     * The io object.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private OutputInterface $io;

    /**
     * A path where database export should be saved to.
     *
     * @var string
     */
    private string $dumpPath;

    /**
     * Command execution start time.
     *
     * @var int
     */
    private int $timeStart;

    /**
     * ExportDbCommand constructor.
     *
     * @param string           $databaseDumpFolder
     * @param ExtractorFactory $extractorFactory
     * @param DBConnector      $connector
     * @param Filesystem       $filesystem
     */
    public function __construct(
        private readonly string $databaseDumpFolder,
        private readonly ExtractorFactory $extractorFactory,
        private readonly DBConnector $connector,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct();
        $this->timeStart = time();
    }

    /**
     * Configure a command.
     *
     * @return void
     */
    public function configure(): void
    {
        $this
            ->addArgument(
                'dsn',
                InputArgument::REQUIRED,
                'Should match the pattern: "driver://user:password@host:port/database"'
            )
            ->addArgument(
                'config-path',
                InputArgument::REQUIRED,
                'A path to the config file with DB dump settings. See .example.dbaconfig.yml'
            );
    }

    /**
     * Do the database dump.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws DsnNotValidException
     * @throws Exception
     * @throws InvalidExtractorInterfaceException
     * @throws StructureExtractorNotFoundException
     * @throws DataExtractorNotFoundException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Get arguments.
            $dsn = $input->getArgument('dsn');
            $configPath = $input->getArgument('config-path');

            // Initialize some variables.
            $this->io = new SymfonyStyle($input, $output);
            $this->connection = $this->connector->create($dsn);
            $this->configurationManager =  new ConfigurationManager($configPath);
            $this->anonymizer = new Anonymizer($this->configurationManager);
            $this->dataExtractor = $this->extractorFactory->createDataExtractor($this->connection, $this->configurationManager, $this->anonymizer);

            $folderName = $this->getNewDumpFolderName($this->connection->getDatabase());
            $this->dumpPath = $this->getDumpFolderPath($folderName);
            $this->createDumpFolder($this->dumpPath);

            // Declare the steps.
            $steps[] = [
                'title' => 'Database structure export',
                'method' => 'dumpStructure',
            ];
            $steps[] = [
                'title' => 'Database tables export',
                'method' => 'dumpTables',
            ];
            $steps[] = [
                'title' => 'Database entities export',
                'method' => 'dumpEntities',
            ];
            $totalSteps = count($steps);

            // Dump the database.
            foreach ($steps as $num => $step) {
                $this->io->block('Step ' . ($num + 1) . '/' . $totalSteps . '. ' . $step['title']);
                $this->{$step['method']}();
            }
        } catch (PDOException $e) {
            $this->io->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->writeln('', false);
        $this->writeln('Woohoo!');
        $this->io->success('Export completed.');

        return Command::SUCCESS;
    }

    /**
     * Dumps db structure.
     *
     * @return void
     *
     * @throws \App\Exception\Service\Extractor\InvalidExtractorInterfaceException
     * @throws \App\Exception\Service\Extractor\StructureExtractorNotFoundException
     */
    public function dumpStructure(): void
    {
        if ($this->configurationManager->shouldSkip('structure')) {
            $this->writeln('Skipping.');

            return;
        }

        $this->write("Exporting DB structure...");
        $structureExtractor = $this->extractorFactory->createStructureExtractor($this->connection);
        $structureExtractor->dumpStructure($this->dumpPath);
        $this->writeln(' done.', false);
    }

    /**
     * Dumps db tables (from database.tables config section).
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function dumpTables(): void
    {
        if ($this->configurationManager->shouldSkip('tables')) {
            $this->writeln('Skipping.');

            return;
        }

        $tables = $this->connection->createSchemaManager()->listTableNames();
        sort($tables);

        foreach ($tables as $tableName) {
            $tableConfig = $this->configurationManager->getTableConfig($tableName);
            if ($this->dataExtractor->canTableBeDumped($tableName, $tableConfig)) {
                $this->write("Exporting $tableName...");
                $this->dataExtractor->dumpTable($tableName, $this->dumpPath, $tableConfig);
                $this->writeln(' done.', false);
            } else {
                $this->writeln("Skipping $tableName.");
            }
        }
    }

    /**
     * Dumps db entities (from database.entities config section).
     *
     * @return void
     */
    public function dumpEntities(): void
    {
        if ($this->configurationManager->shouldSkip('entities')) {
            $this->writeln('Skipping.');

            return;
        }

        $entities = $this->configurationManager->getEntities();
        foreach ($entities as $entityName => $entityConfig) {
            $entityConfig = $this->configurationManager->getEntityConfig($entityName);
            // Skip entities that are configured to export 0% of data.
            if (empty($entityConfig['get'])) {
                continue;
            }

            $this->write("Exporting $entityName...");
            $this->dataExtractor->dumpEntity($entityName, $this->dumpPath, $entityConfig);
            $this->writeln(' done.', false);
        }
    }

    /**
     * Create structure folder.
     *
     * @param string $path
     *
     * @return void
     */
    private function createDumpFolder(string $path): void
    {
        $this->filesystem->mkdir($path);
    }

    /**
     * Get structure folder path.
     *
     * @param string $folderName
     *   Folder name.
     *
     * @return string
     */
    private function getDumpFolderPath(string $folderName): string
    {
        return $this->databaseDumpFolder . '/' . $folderName;
    }

    /**
     * Get new structure folder name.
     *
     * @param string $name
     *   Folder base name.
     *
     * @return string
     */
    private function getNewDumpFolderName(string $name): string
    {
        return $name . '_' . date('Ymd_His');
    }

    /**
     * Writes a message to the output.
     *
     * @param string $message
     *   A message text.
     * @param bool $withDuration
     *   TRUE - prepend a message with time duration.
     *
     * @return void
     */
    private function write(string $message, bool $withDuration = true): void
    {
        $this->io->write(($withDuration ? '[' . $this->getDurationFormatted() . '] ' : '') . $message);
    }

    /**
     * Writes a message to the output and adds a newline at the end.
     *
     * @param string $message
     *   A message text.
     * @param bool $withDuration
     *   TRUE - prepend a message with time duration.
     *
     * @return void
     */
    private function writeln(string $message, bool $withDuration = true): void
    {
        $this->write($message, $withDuration);
        $this->io->writeln('');
    }

    /**
     * Returns the duration (of command execution).
     *
     * @param int $timeStart
     *   Start time.
     * @param int $timeEnd
     *   End time or current time.
     *
     * @return string
     *   Time interval duration as hh:mm:ss.
     */
    private function getDurationFormatted(int $timeStart = 0, int $timeEnd = 0): string
    {
        $timeStart = $timeStart ?: $this->timeStart;
        $duration = ($timeEnd ?: time()) - $timeStart;
        $days = floor($duration / 86400);
        $duration -= $days * 86400;
        $hours = floor($duration / 3600);
        $duration -= $hours * 3600;
        $minutes = floor($duration / 60);
        $seconds = ($duration - $minutes * 60);
        $res = '';

        if ($days > 0) {
            $res .= $days . 'd ';
        }

        $res .= str_pad((string) $hours, 2, '0', STR_PAD_LEFT);
        $res .= ':' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT);
        $res .= ':' . str_pad((string) $seconds, 2, '0', STR_PAD_LEFT);

        return $res;
    }
}
