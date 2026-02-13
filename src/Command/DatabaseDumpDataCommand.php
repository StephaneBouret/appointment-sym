<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:db:dump-data',
    description: 'Exporte uniquement les données (INSERTs) de la BDD via mysqldump --no-create-info.',
)]
final class DatabaseDumpDataCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('output-dir', null, InputOption::VALUE_REQUIRED, 'Dossier de sortie', '/home/u664242226/backups')
            ->addOption('prefix', null, InputOption::VALUE_REQUIRED, 'Préfixe de fichier', 'data_only')
            ->addOption('gzip', null, InputOption::VALUE_NONE, 'Compresser en .gz')
            ->addOption('binary', null, InputOption::VALUE_REQUIRED, 'Chemin mysqldump', '/usr/bin/mysqldump')
            ->addOption(
                'extra',
                null,
                InputOption::VALUE_REQUIRED,
                'Options mysqldump additionnelles',
                '--no-create-info --complete-insert --extended-insert --single-transaction --quick --lock-tables=false --skip-triggers --default-character-set=utf8mb4'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $outputDir = (string)($input->getOption('output-dir') ?? '/home/u664242226/backups');
        $prefix    = (string)($input->getOption('prefix') ?? 'data_only');
        $gzip      = (bool)$input->getOption('gzip');
        $binary    = (string)($input->getOption('binary') ?? '/usr/bin/mysqldump');
        $extra     = (string)($input->getOption('extra')
            ?? '--no-create-info --complete-insert --extended-insert --single-transaction --quick --lock-tables=false --skip-triggers --default-character-set=utf8mb4');

        if (!is_dir($outputDir) && !@mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
            $io->error("Impossible de créer le dossier : $outputDir");
            return Command::FAILURE;
        }

        $params = $this->connection->getParams();
        $dbName = $params['dbname'] ?? null;
        $host   = $params['host'] ?? '127.0.0.1';
        $port   = $params['port'] ?? '3306';
        $user   = $params['user'] ?? '';
        $pass   = $params['password'] ?? '';

        if (!$dbName) {
            $io->error("Nom de base introuvable");
            return Command::FAILURE;
        }

        $dateTag = (new \DateTimeImmutable())->format('Ymd_His');
        $sqlFile = sprintf('%s/%s_%s.sql', $outputDir, $prefix, $dateTag);

        // Préparation d'un fichier temporaire d'options pour mysqldump (permet d'éviter prompt et quoting)
        $optFile = tempnam(sys_get_temp_dir(), 'mysqldump_');
        $optContent = "[client]\n";
        $optContent .= "user={$user}\n";
        $optContent .= "password={$pass}\n";
        $optContent .= "host=" . ($host ?: 'localhost') . "\n";
        $optContent .= "port=" . ((string)$port ?: '3306') . "\n";
        $optContent .= "protocol=TCP\n";

        if (file_put_contents($optFile, $optContent) === false) {
            $io->error("Impossible d'écrire le fichier d'options mysqldump");
            return Command::FAILURE;
        }

        $cmd = sprintf(
            '%s --defaults-extra-file=%s %s %s > %s',
            escapeshellarg($binary),
            escapeshellarg($optFile),
            $extra,
            escapeshellarg($dbName),
            escapeshellarg($sqlFile)
        );

        $io->writeln("Exécution: $cmd");
        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(3600);
        $process->run();

        // Nettoyage du fichier d'options (même en cas d'erreur)
        @unlink($optFile);

        if (!$process->isSuccessful()) {
            $io->error("mysqldump a échoué:\n" . $process->getErrorOutput());
            return Command::FAILURE;
        }

        if ($gzip) {
            if (function_exists('gzencode')) {
                $data = file_get_contents($sqlFile);
                file_put_contents($sqlFile . '.gz', gzencode($data, 9));
                @unlink($sqlFile);
                $io->success("Dump terminé: " . $sqlFile . '.gz');
                return Command::SUCCESS;
            }
            $io->warning("Pas de gzip binaire et zlib indisponible: dump non compressé");
        }

        $io->success("Dump terminé: " . $sqlFile . ($gzip ? '.gz' : ''));
        return Command::SUCCESS;
    }
}
