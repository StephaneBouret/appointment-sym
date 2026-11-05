<?php

namespace App\Tests\Mail;

use App\Service\SendMailService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

// Deliberately does not extend Prestations\TestKernel: auth_mailer stays real.
final class MailKernel extends \App\Kernel
{
    public function getProjectDir(): string { return dirname(__DIR__, 2); }
    public function getCacheDir(): string { return $this->getProjectDir().'/var/mail-tests/cache'; }
    public function getLogDir(): string { return $this->getProjectDir().'/var/mail-tests/log'; }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->import($this->getProjectDir().'/config/packages/*.yaml');
        $container->import($this->getProjectDir().'/config/services.yaml');
        $container->extension('framework', [
            'test' => true,
            'mailer' => ['dsn' => 'null://null', 'message_bus' => false],
        ]);
        // Public access only; no service implementation or template is replaced.
        $container->services()->alias('test.mail_service', SendMailService::class)->public();
        $container->services()->alias('test.auth_mailer', 'auth_mailer')->public();
    }
}
