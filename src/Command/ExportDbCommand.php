<?php

declare(strict_types=1);

namespace App\Command;

use App\Configuration\ExportDbConfiguration;
use App\Configuration\ExportDbConfigurationInterface;
use App\Exception\DsnNotValidException;
use App\Exception\Service\DDL\DataExtractorNotFoundException;
use App\Exception\Service\DDL\InvalidExtractorInterfaceException;
use App\Exception\Service\DDL\StructureExtractorNotFound;
use App\Infrastructure\DBConnector;
use App\Service\DDL\ExtractorFactory;
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
     * @var \App\Configuration\ExportDbConfigurationInterface
     */
    private ExportDbConfigurationInterface $configuration;

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
    public function __construct(private readonly string $databaseDumpFolder, private readonly ExtractorFactory $extractorFactory, private readonly DBConnector $connector, private readonly Filesystem $filesystem)
    {
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
                'config',
                InputArgument::OPTIONAL,
                'Config file with ruleset of DB dumping. See example .example.site.yml'
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
     * @throws StructureExtractorNotFound
     * @throws DataExtractorNotFoundException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Get arguments.
            $dsn = $input->getArgument('dsn');
            $configFile = $input->getArgument('config');

            // Initialize some variables.
            $this->io = new SymfonyStyle($input, $output);
            $this->connection = $this->connector->create($dsn);
            $this->configuration =  new ExportDbConfiguration($configFile);
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

        $this->writeln('', FALSE);
        $this->writeln('Woohoo!');
        $this->io->success('Export completed.');

        return Command::SUCCESS;
    }

    /**
     * Dumps db structure.
     *
     * @return void
     *
     * @throws \App\Exception\Service\DDL\InvalidExtractorInterfaceException
     * @throws \App\Exception\Service\DDL\StructureExtractorNotFound
     */
    public function dumpStructure(): void
    {
        $this->write("Exporting DB structure...");
        $structureExtractor = $this->extractorFactory->createStructureExtractor($this->connection);
        $structureExtractor->dumpStructure($this->dumpPath);
        $this->writeln(' done.', FALSE);
    }

    /**
     * Dumps db tables (from database.tables config section).
     *
     * @return void
     *
     * @throws \App\Exception\Service\DDL\DataExtractorNotFoundException
     * @throws \App\Exception\Service\DDL\InvalidExtractorInterfaceException
     * @throws \Doctrine\DBAL\Exception
     */
    public function dumpTables()
    {
        $dataExtractor = $this->extractorFactory->createDataExtractor($this->connection, $this->configuration);
        $tables = $this->connection->createSchemaManager()->listTableNames();

        foreach ($tables as $table) {
            if ($dataExtractor->canTableBeDumped($table)) {
                $this->write("Exporting $table...");
                $dataExtractor->dumpTable($table, $this->dumpPath);
                $this->writeln(' done.', FALSE);
            }
            else {
                $this->writeln("Skipping $table.");
            }
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
        return $this->databaseDumpFolder.'/'.$folderName;
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
        return $name.'_'.date('Ymd_His');
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
    private function write(string $message, bool $withDuration = TRUE) {
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
    private function writeln(string $message, bool $withDuration = TRUE) {
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
