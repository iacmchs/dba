<?php
/**
 * @file
 * The entry point to db anonymizer command.
 */

declare(strict_types=1);

namespace App\Command;

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

#[AsCommand(name: 'app:db-export')]
class ExportDbCommand extends Command
{
    /**
     * @var ExtractorFactory
     */
    private ExtractorFactory $extractorFactory;

    /**
     * @param ExtractorFactory $extractorFactory
     */
    public function __construct(ExtractorFactory $extractorFactory)
    {
        parent::__construct();
        $this->extractorFactory = $extractorFactory;
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
                'dsn should match the pattern: "driver:host=your_host;dbname=your_dbname;port=your_port"'
            )
            ->addArgument('username', InputArgument::REQUIRED, 'DB user name')
            ->addArgument('password', InputArgument::REQUIRED, 'DB password');
    }

    /**
     * Run the app:db-export command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws InvalidStructureExtractorInterface
     * @throws StructureExtractorNotFound
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        // @todo Uncompleted method. Should be improved and finished.

        $dsn = $input->getArgument('dsn');
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        $io = new SymfonyStyle($input, $output);

        try {
            $connector = $this->connector->create($dsn);
            $this->extractorFactory->createExtractor($connector)->extractTables();
        } catch (PDOException $e) {
            $io->error("Connection failed: " . $e->getMessage());
            return Command::FAILURE;
        }

        $io->success('Export completed.');
        return Command::SUCCESS;
    }
}
