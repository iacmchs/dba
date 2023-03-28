<?php
/**
 * @file
 * The entry point to db anonymizer command.
 */

declare(strict_types=1);

namespace App\Command;

use App\Exception\DsnNotValidException;
use App\Exception\Service\DDL\InvalidStructureExtractorInterface;
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

#[AsCommand(
    name: 'app:db-export',
    description: 'Dump DB structure to folder <DATABASE_DUMP_FOLDER>/<database-name_Ymd_His>/00_<database-name>_structure.sql SQL file. DATABASE_DUMP_FOLDER is exposed in ENV file.'
)]
class ExportDbCommand extends Command
{
    public function __construct(private readonly ExtractorFactory $extractorFactory, private readonly DBConnector $connector)
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
                'dsn should match the pattern: "driver://user:password@host:port/database"'
            );
    }

    /**
     * Run the app:db-export command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws InvalidStructureExtractorInterface
     * @throws StructureExtractorNotFound
     * @throws DsnNotValidException
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // @todo Uncompleted method. Should be improved and finished.

        $dsn = $input->getArgument('dsn');

        $io = new SymfonyStyle($input, $output);

        try {
            $connector = $this->connector->create($dsn);
            $this->extractorFactory->createExtractor($connector)->dumpStructure();
        } catch (PDOException $e) {
            $io->error("Connection failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        $io->success('Export completed.');
        return Command::SUCCESS;
    }
}
