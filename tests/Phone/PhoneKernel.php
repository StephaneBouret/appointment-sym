<?php

namespace App\Tests\Phone;

use App\Controller\RegistrationController;
use App\Service\AvatarService;
use App\Tests\Prestations\TestKernel;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Mailer\MailerInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class PhoneKernel extends TestKernel
{
    public function getCacheDir(): string { return $this->getProjectDir().'/var/phone-tests/cache'; }
    public function getLogDir(): string { return $this->getProjectDir().'/var/phone-tests/log'; }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);
        $container->extension('framework', ['session' => [
            'storage_factory_id' => 'session.storage.factory.mock_file',
            'save_path' => $this->getProjectDir().'/var/phone-tests/sessions',
        ]]);
        $services = $container->services();
        $services->set(PhoneWriteObserver::class)->decorate('doctrine.orm.default_entity_manager')
            ->args([service('.inner')]);
        $services->set(AvatarService::class, PhoneAvatarService::class)->autowire();
        $services->set('mailer.mailer', PhoneMailer::class);
        $services->alias(MailerInterface::class, 'mailer.mailer');
        $services->set('auth_mailer', PhoneAuthMailer::class)->public();
        // The test injects a login-call observer that delegates to the genuine Security helper.
        $services->set('test.phone_security', Security::class)->synthetic()->public();
        $services->set(RegistrationController::class)->autowire()->autoconfigure()
            ->bind('$security', service('test.phone_security'));
    }
}
