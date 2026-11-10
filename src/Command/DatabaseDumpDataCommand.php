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

        $optFile = null;
        $archiveTemp = null;
        $sqlHandle = null;
        $result = Command::FAILURE;
        $produced = null;
        $error = "Impossible de créer le dossier : $outputDir";
        try {
            if (!is_dir($outputDir) && !@mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
                throw new \RuntimeException();
            }

            $error = 'Impossible de lire les paramètres de connexion';
            $params = $this->connection->getParams();
            $dbName = $params['dbname'] ?? null;
            $host   = $params['host'] ?? '127.0.0.1';
            $port   = $params['port'] ?? '3306';
            $user   = $params['user'] ?? '';
            $pass   = $params['password'] ?? '';

            $error = 'Nom de base introuvable';
            if (!$dbName) {
                throw new \RuntimeException();
            }

            $dateTag = (new \DateTimeImmutable())->format('Ymd_His');
            $sqlFile = sprintf('%s/%s_%s.sql', $outputDir, $prefix, $dateTag);

            $archiveFile = $sqlFile . '.gz';
            $error = "Destination déjà existante : $sqlFile";
            if (file_exists($sqlFile) || is_link($sqlFile)) {
                throw new \RuntimeException();
            }
            $error = "Destination déjà existante : $archiveFile";
            if ($gzip && (file_exists($archiveFile) || is_link($archiveFile))) {
                throw new \RuntimeException();
            }

            $error = "Impossible de créer le fichier d'options mysqldump";
            $options = @tempnam(sys_get_temp_dir(), 'mysqldump_');
            if ($options === false) {
                throw new \RuntimeException();
            }
            $optFile = $options;
            $error = "Impossible de protéger le fichier d'options mysqldump";
            if (!@chmod($optFile, 0600)) {
                throw new \RuntimeException();
            }
            $optContent = "[client]\n";
            $optContent .= "user={$user}\n";
            $optContent .= "password={$pass}\n";
            $optContent .= "host=" . ($host ?: 'localhost') . "\n";
            $optContent .= "port=" . ((string)$port ?: '3306') . "\n";
            $optContent .= "protocol=TCP\n";

            $error = "Impossible d'écrire complètement le fichier d'options mysqldump";
            if (@file_put_contents($optFile, $optContent) !== strlen($optContent)) {
                throw new \RuntimeException();
            }

            // Exclusive reservation prevents concurrent runs from truncating an existing SQL.
            $error = "Impossible de réserver le fichier SQL sans écrasement : $sqlFile";
            $sqlHandle = @fopen($sqlFile, 'x');
            if ($sqlHandle === false) {
                throw new \RuntimeException();
            }
            $error = "Impossible de fermer le fichier SQL réservé : $sqlFile";
            if (!@fclose($sqlHandle)) {
                throw new \RuntimeException();
            }
            $sqlHandle = null;

            $error = "Exception du processus mysqldump ; SQL non validé conservé : $sqlFile";
            $cmd = sprintf(
                '%s --defaults-extra-file=%s %s %s > %s',
                escapeshellarg($binary),
                escapeshellarg($optFile),
                $extra,
                escapeshellarg($dbName),
                escapeshellarg($sqlFile)
            );

            // Neither the command line nor process/exception output is safe to print:
            // it can include connection identifiers or user-supplied credentials.
            $io->writeln('Exécution de mysqldump');
            $process = Process::fromShellCommandline($cmd);
            $process->setTimeout(3600);
            $process->run();

            $error = "mysqldump a échoué ; SQL non validé conservé : $sqlFile";
            if (!$process->isSuccessful()) {
                throw new \RuntimeException();
            }

            $produced = $sqlFile;
            $error = "Nettoyage du fichier d'options mysqldump impossible ; SQL conservé : $sqlFile";
            if (!$this->removeFile($optFile)) {
                throw new \RuntimeException();
            }
            $optFile = null;
            if ($gzip) {
                $error = "zlib indisponible ; dump SQL conservé, aucune archive créée : $sqlFile";
                if (!function_exists('gzencode') || !function_exists('gzdecode')) {
                    throw new \RuntimeException();
                }
                $error = "Impossible de lire le SQL ; SQL conservé : $sqlFile";
                $data = @file_get_contents($sqlFile);
                if ($data === false) {
                    throw new \RuntimeException();
                }
                $error = "Compression gzip échouée ; SQL conservé : $sqlFile";
                $encoded = @gzencode($data, 9);
                if ($encoded === false) {
                    throw new \RuntimeException();
                }
                $error = "Impossible de créer le temporaire gzip dans le dossier de destination ; SQL conservé : $sqlFile";
                $temporary = @tempnam(dirname($archiveFile), '.dump-gzip-');
                if ($temporary === false) {
                    throw new \RuntimeException();
                }
                $archiveTemp = $temporary;
                // tempnam may fall back to the system temp directory: refuse that fallback.
                if (realpath(dirname($archiveTemp)) !== realpath(dirname($archiveFile))) {
                    throw new \RuntimeException();
                }
                $error = "Écriture gzip incomplète ; SQL conservé : $sqlFile";
                if (@file_put_contents($archiveTemp, $encoded) !== strlen($encoded)) {
                    throw new \RuntimeException();
                }
                $error = "Vérification gzip échouée ; SQL conservé : $sqlFile";
                $stored = @file_get_contents($archiveTemp);
                // Compare persisted bytes AND decompress them: no hash-only integrity claim.
                if ($stored === false || $stored !== $encoded || @gzdecode($stored) !== $data) {
                    throw new \RuntimeException();
                }
                $error = "Impossible de finaliser l'archive sans écrasement ; SQL conservé : $sqlFile";
                // Unlike rename(), link() atomically refuses an existing destination.
                // Both names are on the same filesystem; only complete, verified bytes are published.
                if (!@link($archiveTemp, $archiveFile)) {
                    throw new \RuntimeException();
                }
                $produced = $archiveFile;
                $error = "Archive valide créée : $archiveFile ; suppression du temporaire gzip impossible ; SQL conservé : $sqlFile";
                if (!$this->removeFile($archiveTemp)) {
                    throw new \RuntimeException();
                }
                $archiveTemp = null;
                $error = "Archive valide créée : $archiveFile ; suppression du SQL impossible : $sqlFile";
                if (!$this->removeFile($sqlFile)) {
                    throw new \RuntimeException();
                }
            }
            $result = Command::SUCCESS;
        } catch (\Throwable) {
            // Use only our stage message, never exception details containing secrets.
            $io->error($error);
        } finally {
            if (is_resource($sqlHandle)) {
                try {
                    if (!@fclose($sqlHandle)) {
                        $io->error('Fermeture du fichier SQL réservé impossible');
                        $result = Command::FAILURE;
                    }
                } catch (\Throwable) {
                    $io->error('Fermeture du fichier SQL réservé impossible');
                    $result = Command::FAILURE;
                }
            }
            foreach ([[$optFile, "fichier d'options mysqldump"], [$archiveTemp, 'temporaire gzip']] as [$path, $kind]) {
                if ($path !== null && !$this->removeFile($path)) {
                    $io->error("Nettoyage impossible du $kind : $path");
                    $result = Command::FAILURE;
                }
            }
        }
        if ($result === Command::SUCCESS) {
            $io->success("Dump terminé: $produced");
        } elseif ($produced !== null) {
            $io->note("Fichier produit conservé : $produced");
        }
        return $result;
    }

    private function removeFile(string $path): bool
    {
        try {
            return @unlink($path);
        } catch (\Throwable) {
            return false;
        }
    }
}
