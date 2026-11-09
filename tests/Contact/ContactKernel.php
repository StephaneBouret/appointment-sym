<?php

namespace App\Tests\Contact;

use App\Controller\ContactController;
use App\Tests\Prestations\TestKernel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class ContactKernel extends TestKernel
{
    public function getCacheDir(): string { return $this->getProjectDir().'/var/contact-tests/cache'; }
    public function getLogDir(): string { return $this->getProjectDir().'/var/contact-tests/log'; }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        parent::configureContainer($container);
        // Real controller, form, security/CSRF, templates, business subscriber and null mailer.
        $container->services()->set(ContactWriteObserver::class)
            ->args([service('doctrine.orm.entity_manager')])->public();
        $container->services()->set(ContactController::class)->autowire()->autoconfigure()
            ->bind('$em', service(ContactWriteObserver::class));
    }
}
