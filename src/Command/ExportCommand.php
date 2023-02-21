<?php

declare(strict_types=1);

namespace App\Command;

use App\Exception\Service\DDL\InvalidStructureExtractorInterface;
use App\Exception\Service\DDL\StructureExtractorNotFound;
use App\Service\DDL\ExtractorFactory;
use PDO;
use PDOException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:export')]
class ExportCommand extends Command
{
    private ExtractorFactory $extractorFactory;

    public function __construct(ExtractorFactory $extractorFactory)
    {
        parent::__construct();
        $this->extractorFactory = $extractorFactory;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws InvalidStructureExtractorInterface
     * @throws StructureExtractorNotFound
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $host = '192.168.17.155';
        $dbname = 'passport';
        $username = 'passport';
        $password = 'P@ssw0rd';

        try {
            $pdo = new PDO("pgsql:host=$host;dbname=$dbname;port=5532", $username, $password);

            $struct = $this->extractorFactory->createExtractor($pdo)->extractTables();
//            dump($struct->toDDL());

        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }

        return Command::SUCCESS;
    }
}
