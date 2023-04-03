<?php

declare(strict_types=1);

namespace App\Command;

use App\Configuration\ExportDbConfiguration;
use App\Exception\DsnNotValidException;
use App\Exception\Service\DDL\DataExtractorNotFoundException;
use App\Exception\Service\DDL\InvalidExtractorInterfaceException;
use App\Exception\Service\DDL\StructureExtractorNotFound;
use App\Infrastructure\DBConnector;
use App\Service\DDL\ExtractorFactory;
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
     * Run the app:db-export command.
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
        // @todo Uncompleted method. Should be improved and finished.

        $dsn = $input->getArgument('dsn');
        $configFile = $input->getArgument('config');

        $io = new SymfonyStyle($input, $output);

        try {
            $connector = $this->connector->create($dsn);
            $structureExtractor = $this->extractorFactory->createStructureExtractor($connector);

            $configuration =  new ExportDbConfiguration($configFile);
            $dataExtractor = $this->extractorFactory->createDataExtractor($connector, $configuration);


            $folderName = $this->getNewDumpFolderName($connector->getDatabase());
            $folderPath = $this->getDumpFolderPath($folderName);
            $this->createDumpFolder($folderPath);
            $structureExtractor->dumpStructure($folderPath);
            $io->success('Structure export completed.');

            $tablePrefixNameCounter = 10;
            $tables = $connector->createSchemaManager()->listTableNames();

            foreach ($tables as $table) {
                if ($dataExtractor->isTableCanBeDumped($table)) {
                    $io->success('Table '.$table.' skipped.');
                }

                $dataExtractor->dumpTable($table, $folderPath, (string) $tablePrefixNameCounter);
                $io->success('Table '.$table.' export completed.');

                $tablePrefixNameCounter++;
            }
        } catch (PDOException $e) {
            $io->error("Connection failed: ".$e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Export completed.');

        return Command::SUCCESS;
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
     *
     * @return string
     */
    private function getNewDumpFolderName(string $name): string
    {
        return $name.'_'.date('Ymd_His');
    }
}
