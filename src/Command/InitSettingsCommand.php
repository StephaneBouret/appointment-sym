<?php

namespace App\Command;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:init-settings',
    description: 'Crée les settings manquants (idempotent).',
)]
final class InitSettingsCommand extends Command
{
    public function __construct(
        private readonly SettingRepository $repo,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $created = $this->ensureBoolSetting(Setting::KEY_MAINTENANCE, false, $output);

        if ($created) {
            $this->em->flush();
            $output->writeln('<info>Settings initialisés ✅</info>');
        } else {
            $output->writeln('<comment>Aucun setting à créer.</comment>');
        }

        return Command::SUCCESS;
    }

    private function ensureBoolSetting(string $key, bool $default, OutputInterface $output): bool
    {
        $existing = $this->repo->findOneBy(['settingKey' => $key]);

        if ($existing) {
            $output->writeln(sprintf('• <comment>%s</comment> déjà présent', $key));
            return false;
        }

        $this->em->persist(new Setting($key, $default));
        $output->writeln(sprintf('• <info>%s</info> créé (default=%s)', $key, $default ? 'true' : 'false'));
        return true;
    }
}
