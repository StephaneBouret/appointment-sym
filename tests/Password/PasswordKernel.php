<?php

namespace App\Tests\Password;

use App\Google\GoogleService;
use App\Tests\Prestations\TestKernel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Mailer\MailerInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class PasswordKernel extends TestKernel
{
    public function getCacheDir(): string { return $this->getProjectDir().'/var/password-tests/cache'; }
    public function getLogDir(): string { return $this->getProjectDir().'/var/password-tests/log'; }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);
        $container->extension('framework', ['session' => [
            'storage_factory_id' => 'session.storage.factory.mock_file',
            'save_path' => $this->getProjectDir().'/var/password-tests/sessions',
        ]]);
        // Avoid persisting synthetic credentials in SQL diagnostic logs.
        $container->extension('doctrine', ['dbal' => ['logging' => false, 'profiling' => false]]);
        $services = $container->services();
        $services->set(PasswordWriteObserver::class)->decorate('doctrine.orm.default_entity_manager')->args([service('.inner')]);
        $services->set(PasswordFormObserver::class)->tag('form.type_extension');
        $services->set(PasswordOriginObserver::class)->tag('kernel.event_subscriber');
        $services->set('mailer.mailer', PasswordMailer::class);
        $services->alias(MailerInterface::class, 'mailer.mailer');
        $services->set('auth_mailer', PasswordAuthMailer::class)->public();
        $services->set(GoogleService::class, PasswordGoogleService::class)->autowire()
            ->args(['unused', 'unused', service('http_client'), service('cache.app')]);
        $services->alias('test.password_trusted_manager', 'scheb_two_factor.trusted_device_manager')->public();
        $services->alias('test.password_trusted_storage', 'scheb_two_factor.trusted_token_storage')->public();
    }
}
